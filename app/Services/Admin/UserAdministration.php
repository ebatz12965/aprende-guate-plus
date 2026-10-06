<?php

namespace App\Services\Admin;

use App\Models\MentorApplication;
use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use App\Support\AdminAccess;
use App\Support\Audit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Reglas de la gestión de usuarios desde administración.
 *
 * - Un administrador delegado no puede modificar cuentas con permisos de administración: solo el super administrador.
 * - Solo se asignan o retiran roles cuyos permisos el actor también tiene (nadie se sube de nivel a sí mismo ni a otros).
 * - Siempre queda al menos un super administrador activo.
 */
class UserAdministration
{
    private const OPEN_SESSION_STATUSES = ['requested', 'accepted', 'scheduled'];

    /** Motivo por el que el actor no puede modificar la cuenta, o null si puede. */
    public function protection(User $actor, User $target): ?string
    {
        if ($actor->is($target) && ! $actor->isSuperAdmin()) {
            return 'Tu propia cuenta se edita desde Configuración.';
        }
        if (! $actor->isSuperAdmin() && $target->canAccessAdmin()) {
            return 'Solo un super administrador puede modificar cuentas con permisos de administración.';
        }

        return null;
    }

    /** @return Collection<int, Role> */
    public function assignableRoles(User $actor): Collection
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        if ($actor->isSuperAdmin()) {
            return $roles;
        }
        $mine = $actor->adminPermissions();

        return $roles->reject(fn (Role $role) => $role->name === AdminAccess::SUPER_ROLE
            || $role->permissions->pluck('name')->diff($mine)->isNotEmpty())->values();
    }

    public function activeSuperAdmins(): int
    {
        return User::role(AdminAccess::SUPER_ROLE)->notSuspended()->count();
    }

    /** @param  array{name: string, email: string, password: string}  $data  @param  list<string>  $roles */
    public function create(User $actor, array $data, array $roles = []): User
    {
        $user = DB::transaction(function () use ($actor, $data, $roles) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
            $user->assignRole(AdminAccess::BASE_ROLE);
            Audit::log('usuarios', 'Creó la cuenta', $user, ['nombre' => $user->name, 'correo' => $user->email]);
            if ($roles !== []) {
                $this->syncRoles($actor, $user, $roles);
            }

            return $user;
        });

        return $user;
    }

    /** @param  array{name: string, email: string, password?: ?string}  $data */
    public function updateDetails(User $actor, User $target, array $data): void
    {
        $this->ensureCanManage($actor, $target, 'name');
        $changes = [];
        foreach (['name' => 'nombre', 'email' => 'correo'] as $field => $label) {
            if ($target->{$field} !== $data[$field]) {
                $changes[$label] = ['antes' => $target->{$field}, 'después' => $data[$field]];
                $target->{$field} = $data[$field];
            }
        }
        if (filled($data['password'] ?? null)) {
            $target->password = Hash::make($data['password']);
            $changes['contraseña'] = 'cambiada';
        }
        if ($changes === []) {
            return;
        }
        $target->save();
        Audit::log('usuarios', 'Editó los datos de la cuenta', $target, $changes);
    }

    /** @param  list<string>  $names */
    public function syncRoles(User $actor, User $target, array $names): void
    {
        $this->ensureCanManage($actor, $target, 'roles');
        $names = array_values(array_unique([...$names, AdminAccess::BASE_ROLE]));
        $current = $target->getRoleNames()->all();
        $added = array_values(array_diff($names, $current));
        $removed = array_values(array_diff($current, $names));
        if ($added === [] && $removed === []) {
            return;
        }

        $assignable = $this->assignableRoles($actor)->pluck('name');
        foreach ([...$added, ...$removed] as $role) {
            if (! $assignable->contains($role)) {
                $this->fail('roles', 'No puedes asignar ni retirar el rol «'.AdminAccess::roleLabel($role).'»: incluye permisos que tú no tienes.');
            }
        }
        if (in_array(AdminAccess::SUPER_ROLE, $removed, true)) {
            if ($actor->is($target)) {
                $this->fail('roles', 'No puedes retirarte el rol de super administrador.');
            }
            if ($this->activeSuperAdmins() <= 1) {
                $this->fail('roles', 'Debe quedar al menos un super administrador activo.');
            }
        }
        if (in_array(AdminAccess::MENTOR_ROLE, $removed, true)) {
            $open = MentorshipSession::where('mentor_id', $target->id)->whereIn('status', self::OPEN_SESSION_STATUSES)->count();
            if ($open > 0) {
                $this->fail('roles', "No se puede retirar el rol de mentor: tiene {$open} ".Str::plural('sesión', $open).' de mentoría abierta. Primero deben cancelarse o resolverse.');
            }
        }

        DB::transaction(function () use ($target, $names, $added, $removed) {
            $target->syncRoles($names);
            $withoutApplication = false;
            if (in_array(AdminAccess::MENTOR_ROLE, $added, true)) {
                // Asignado por administración: el perfil se crea con valores básicos y la persona lo completa.
                MentorProfile::firstOrCreate(['user_id' => $target->id], ['session_duration_minutes' => 30]);
                $withoutApplication = ! MentorApplication::where('user_id', $target->id)->where('status', 'approved')->exists();
            }
            Audit::log('roles', 'Cambió los roles de la cuenta', $target, array_filter([
                'agregados' => array_map(AdminAccess::roleLabel(...), $added),
                'retirados' => array_map(AdminAccess::roleLabel(...), $removed),
                'mentor sin postulación' => $withoutApplication ?: null,
            ]));
        });
    }

    public function suspend(User $actor, User $target, ?Carbon $until, string $reason): int
    {
        if ($actor->is($target)) {
            $this->fail('suspension', 'No puedes suspender tu propia cuenta.');
        }
        $this->ensureCanManage($actor, $target, 'suspension');
        if ($target->isSuperAdmin() && ! $target->isSuspended() && $this->activeSuperAdmins() <= 1) {
            $this->fail('suspension', 'Debe quedar al menos un super administrador activo.');
        }

        $cancelled = DB::transaction(function () use ($actor, $target, $until, $reason) {
            $target->forceFill([
                'suspended_at' => now(),
                'suspended_until' => $until,
                'suspension_reason' => $reason,
                'suspended_by' => $actor->id,
            ]);
            // Invalida la cookie de "recordarme": la cuenta no vuelve a entrar sola.
            $target->setRememberToken(Str::random(60));
            $target->save();

            return $this->cancelOpenMentorships($actor, $target);
        });

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $target->id)->delete();
        }

        Audit::log('usuarios', $until ? 'Suspendió la cuenta temporalmente' : 'Suspendió la cuenta de forma indefinida', $target, array_filter([
            'hasta' => $until?->format('d/m/Y H:i'),
            'motivo' => $reason,
            'mentorías canceladas' => $cancelled ?: null,
        ]));

        return $cancelled;
    }

    public function reactivate(User $actor, User $target): void
    {
        $this->ensureCanManage($actor, $target, 'suspension');
        if ($target->suspended_at === null) {
            return;
        }
        $wasActive = ! $target->isSuspended();
        $target->forceFill(['suspended_at' => null, 'suspended_until' => null, 'suspension_reason' => null, 'suspended_by' => null])->save();
        Audit::log('usuarios', $wasActive ? 'Cerró una suspensión ya vencida' : 'Reactivó la cuenta', $target);
    }

    public function delete(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            $this->fail('delete', 'No puedes eliminar tu propia cuenta.');
        }
        if ($target->isSuperAdmin() && $this->activeSuperAdmins() <= 1 && ! $target->isSuspended()) {
            $this->fail('delete', 'Debe quedar al menos un super administrador activo.');
        }
        Audit::log('usuarios', 'Eliminó la cuenta definitivamente', $target, [
            'nombre' => $target->name,
            'correo' => $target->email,
            'roles' => $target->getRoleNames()->map(AdminAccess::roleLabel(...))->all(),
        ]);
        $target->delete();
    }

    /** Cancela las mentorías abiertas de una cuenta suspendida y avisa a la otra persona sin revelar la suspensión. */
    private function cancelOpenMentorships(User $actor, User $target): int
    {
        $sessions = MentorshipSession::with(['student', 'mentor'])
            ->where(fn ($query) => $query->where('student_id', $target->id)->orWhere('mentor_id', $target->id))
            ->whereIn('status', self::OPEN_SESSION_STATUSES)
            ->get();

        foreach ($sessions as $session) {
            $message = 'La sesión sobre “'.$session->topic.'” se canceló porque la otra persona ya no está disponible en la plataforma.';
            $session->update(['status' => 'cancelled', 'cancelled_by' => $actor->id, 'cancelled_at' => now(), 'cancellation_message' => $message]);
            $recipient = $session->student_id === $target->id ? $session->mentor : $session->student;
            $recipient?->notify(new PlatformAlertNotification(
                category: 'mentorship',
                title: 'Sesión de mentoría cancelada',
                body: $message,
                url: $session->student_id === $target->id ? route('mentor.dashboard') : route('adaptive.mentorship'),
                actionLabel: 'Ver mentorías',
            ));
        }

        return $sessions->count();
    }

    private function ensureCanManage(User $actor, User $target, string $field): void
    {
        if ($reason = $this->protection($actor, $target)) {
            $this->fail($field, $reason);
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
