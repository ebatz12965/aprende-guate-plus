<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveUserRequest;
use App\Models\User;
use App\Services\Admin\UserAdministration;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserAdministration $administration) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'in:activas,suspendidas'],
            'rol' => ['nullable', 'string', 'max:120'],
        ]);

        $users = User::with('roles')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($inner) => $inner->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when(($filters['estado'] ?? null) === 'activas', fn ($query) => $query->notSuspended())
            ->when(($filters['estado'] ?? null) === 'suspendidas', fn ($query) => $query->suspended())
            ->when($filters['rol'] ?? null, fn ($query, $role) => $query->whereHas('roles', fn ($inner) => $inner->where('name', $role)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::orderBy('name')->pluck('name'),
            'suspendedCount' => User::suspended()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.create', ['assignable' => $this->assignableFor($request->user())]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $request->user()->can('roles.asignar') ? ($data['roles'] ?? []) : [];
        $user = $this->administration->create($request->user(), $data, $roles);

        return redirect()->route('admin.users.edit', $user)->with('success', 'Cuenta creada correctamente.');
    }

    public function edit(Request $request, User $user): View
    {
        $actor = $request->user();
        $user->load('roles', 'suspendedBy');

        return view('admin.users.edit', [
            'user' => $user,
            'protection' => $this->administration->protection($actor, $user),
            'assignable' => $this->assignableFor($actor),
            'allRoles' => Role::orderBy('name')->get(),
            'history' => Activity::with('causer')->where('subject_type', $user->getMorphClass())->where('subject_id', $user->id)->latest()->take(15)->get(),
        ]);
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $this->administration->updateDetails($request->user(), $user, $request->validated());

        return redirect()->route('admin.users.edit', $user)->with('success', 'Datos actualizados.');
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['roles' => ['array'], 'roles.*' => ['string', Rule::exists('roles', 'name')]]);
        $this->administration->syncRoles($request->user(), $user, $data['roles'] ?? []);

        return redirect()->route('admin.users.edit', $user)->with('success', 'Roles actualizados.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'duration' => ['required', 'in:1,3,7,30,custom,permanent'],
            'until' => ['nullable', 'required_if:duration,custom', 'date', 'after:now'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $until = match ($data['duration']) {
            'permanent' => null,
            'custom' => Carbon::parse($data['until']),
            default => now()->addDays((int) $data['duration']),
        };
        $cancelled = $this->administration->suspend($request->user(), $user, $until, $data['reason']);

        $message = 'Cuenta suspendida '.($until ? 'hasta el '.$until->format('d/m/Y H:i') : 'de forma indefinida').'.';
        if ($cancelled > 0) {
            $message .= " Se cancelaron {$cancelled} ".($cancelled === 1 ? 'mentoría abierta' : 'mentorías abiertas').' y se avisó a la otra persona.';
        }

        return redirect()->route('admin.users.edit', $user)->with('success', $message);
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->administration->reactivate($request->user(), $user);

        return redirect()->route('admin.users.edit', $user)->with('success', 'La cuenta fue reactivada.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $request->validate(['confirm_email' => ['required', 'string', Rule::in([$user->email])]], [
            'confirm_email.in' => 'Escribe el correo exacto de la cuenta para confirmar.',
        ]);
        $this->administration->delete($request->user(), $user);

        return redirect()->route('admin.users.index')->with('success', 'La cuenta y sus datos fueron eliminados.');
    }

    /** @return array<string, bool> rol => ¿puede asignarlo este actor? */
    private function assignableFor(User $actor): array
    {
        if (! $actor->can('roles.asignar')) {
            return [];
        }
        $assignable = $this->administration->assignableRoles($actor)->pluck('name');

        return Role::orderBy('name')->pluck('name')
            ->sortBy(fn (string $role) => AdminAccess::sortKey($role))
            ->mapWithKeys(fn (string $role) => [$role => $assignable->contains($role)])
            ->all();
    }
}
