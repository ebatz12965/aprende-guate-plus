<?php

namespace Tests\Feature;

use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(['Admin', 'Student']);

        return $user;
    }

    /** @param  list<string>  $permissions */
    private function delegated(array $permissions, string $roleName = 'Soporte'): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);
        $user = User::factory()->create();
        $user->assignRole(['Student', $roleName]);

        return $user;
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        return $user;
    }

    public function test_super_admin_assigns_several_roles_and_student_is_always_kept(): void
    {
        $admin = $this->superAdmin();
        $student = $this->student();

        $this->actingAs($admin)->put(route('admin.users.roles', $student), ['roles' => ['Instructor']])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(['Student', 'Instructor'], $student->fresh()->getRoleNames()->all());
        // Mentor asignado directamente: se crea su perfil y queda registrado que no pasó por la solicitud.
        $this->assertTrue(MentorProfile::where('user_id', $student->id)->exists());
        $entry = Activity::where('log_name', 'roles')->latest('id')->first();
        $this->assertSame($admin->id, $entry->causer_id);
        $this->assertTrue($entry->properties['mentor sin postulación']);
    }

    public function test_delegated_admin_only_reaches_the_sections_of_their_permissions(): void
    {
        $support = $this->delegated(['usuarios.ver', 'mentores.revisar']);

        $this->actingAs($support)->get(route('admin.dashboard'))->assertOk()->assertSee('Solicitudes de mentor')->assertDontSee('Orquestador IA');
        $this->actingAs($support)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($support)->get(route('admin.mentor-applications.index'))->assertOk();
        foreach (['admin.ai-orchestrator.index', 'admin.mail-settings.edit', 'admin.ai-settings.edit', 'admin.ai-pulse.index', 'admin.audit.index', 'admin.roles.create', 'admin.users.create'] as $route) {
            $this->actingAs($support)->get(route($route))->assertForbidden();
        }
        // Un estudiante sin permisos no entra al panel.
        $this->actingAs($this->student())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_delegated_admin_cannot_escalate_privileges(): void
    {
        $support = $this->delegated(['usuarios.ver', 'roles.asignar']);
        Role::findOrCreate('Contenido', 'web')->syncPermissions(['avisos.publicar']);
        $student = $this->student();

        $this->actingAs($support)->put(route('admin.users.roles', $student), ['roles' => ['Admin']])->assertSessionHasErrors('roles');
        $this->actingAs($support)->put(route('admin.users.roles', $student), ['roles' => ['Contenido']])->assertSessionHasErrors('roles');
        $this->actingAs($support)->put(route('admin.users.roles', $student), ['roles' => ['Soporte']])->assertSessionHasNoErrors();
        $this->assertTrue($student->fresh()->hasRole('Soporte'));

        // Ni a sí mismo ni a otra cuenta con permisos de administración.
        $this->actingAs($support)->put(route('admin.users.roles', $support), ['roles' => ['Admin']])->assertSessionHasErrors('roles');
        $this->actingAs($support)->put(route('admin.users.roles', $this->superAdmin()), ['roles' => []])->assertSessionHasErrors('roles');
        $this->assertFalse($support->fresh()->isSuperAdmin());
    }

    public function test_delegated_admin_cannot_suspend_or_edit_other_admins(): void
    {
        $support = $this->delegated(['usuarios.ver', 'usuarios.gestionar', 'usuarios.suspender']);
        $admin = $this->superAdmin();

        $this->actingAs($support)->post(route('admin.users.suspend', $admin), ['duration' => '7', 'reason' => 'Prueba de escalada'])->assertSessionHasErrors('suspension');
        $this->actingAs($support)->put(route('admin.users.update', $admin), ['name' => 'Otro', 'email' => $admin->email])->assertSessionHasErrors('name');
        $this->assertFalse($admin->fresh()->isSuspended());
        $this->actingAs($support)->delete(route('admin.users.destroy', $this->student()))->assertForbidden();
    }

    public function test_at_least_one_super_admin_always_remains(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('admin.users.roles', $admin), ['roles' => ['Student']])->assertSessionHasErrors('roles');
        $this->actingAs($admin)->post(route('admin.users.suspend', $admin), ['duration' => 'permanent', 'reason' => 'Me suspendo'])->assertSessionHasErrors('suspension');
        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_suspended_account_cannot_log_in_and_its_open_session_is_closed(): void
    {
        $admin = $this->superAdmin();
        $student = $this->student();

        $this->actingAs($admin)->post(route('admin.users.suspend', $student), ['duration' => '7', 'reason' => 'Lenguaje ofensivo en mentorías'])->assertSessionHasNoErrors();
        $this->assertTrue($student->fresh()->isSuspended());

        // La sesión que ya estaba abierta se cierra en la siguiente petición.
        $this->actingAs($student->fresh())->get(route('adaptive.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        // El motivo se muestra solo después de una contraseña correcta.
        $this->post('/login', ['email' => $student->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => $student->fresh()->suspensionMessage()]);
        $this->assertGuest();
        $this->post('/login', ['email' => $student->email, 'password' => 'incorrecta'])->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_temporary_suspension_expires_on_its_own_and_can_be_lifted_early(): void
    {
        $admin = $this->superAdmin();
        $student = $this->student();
        $this->actingAs($admin)->post(route('admin.users.suspend', $student), ['duration' => '1', 'reason' => 'Revisión de una denuncia']);

        $this->travel(25)->hours();
        $this->assertFalse($student->fresh()->isSuspended());
        $this->post('/login', ['email' => $student->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->travelBack();

        $other = $this->student();
        $this->actingAs($admin)->post(route('admin.users.suspend', $other), ['duration' => 'permanent', 'reason' => 'Suplantación de identidad']);
        $this->actingAs($admin)->delete(route('admin.users.reactivate', $other))->assertSessionHasNoErrors();
        $this->assertFalse($other->fresh()->isSuspended());
        $this->assertSame(['Reactivó la cuenta', 'Suspendió la cuenta de forma indefinida'],
            Activity::where('subject_id', $other->id)->latest('id')->take(2)->pluck('description')->all());
    }

    public function test_suspending_a_mentor_hides_them_and_cancels_open_sessions(): void
    {
        Notification::fake();
        $admin = $this->superAdmin();
        $mentor = $this->student();
        $mentor->assignRole('Instructor');
        MentorProfile::create(['user_id' => $mentor->id, 'headline' => 'Analista de datos', 'session_duration_minutes' => 30]);
        $student = $this->student();
        $session = MentorshipSession::create(['student_id' => $student->id, 'mentor_id' => $mentor->id, 'topic' => 'Excel', 'status' => 'requested']);

        $this->actingAs($admin)->post(route('admin.users.suspend', $mentor), ['duration' => '30', 'reason' => 'Conducta inapropiada'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 mentoría abierta'));

        $this->assertSame('cancelled', $session->fresh()->status);
        Notification::assertSentTo($student, PlatformAlertNotification::class);
        // La persona suspendida no recibe avisos y no aparece en la lista de mentores.
        $this->assertSame([], (new PlatformAlertNotification('platform', 'Hola', 'Texto'))->via($mentor->fresh()));
        $this->actingAs($student)->get(route('adaptive.mentorship'))->assertOk()->assertDontSee('Analista de datos');
    }

    public function test_mentor_role_cannot_be_removed_while_sessions_are_open(): void
    {
        $admin = $this->superAdmin();
        $mentor = $this->student();
        $mentor->assignRole('Instructor');
        MentorshipSession::create(['student_id' => $this->student()->id, 'mentor_id' => $mentor->id, 'topic' => 'CV', 'status' => 'scheduled', 'scheduled_at' => now()->addDay()]);

        $this->actingAs($admin)->put(route('admin.users.roles', $mentor), ['roles' => ['Student']])->assertSessionHasErrors('roles');
        $this->assertTrue($mentor->fresh()->hasRole('Instructor'));
    }

    public function test_super_admin_manages_custom_roles(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.roles.store'), ['name' => 'Soporte', 'permissions' => ['usuarios.ver', 'usuarios.suspender']])->assertRedirect(route('admin.roles.index'));
        $role = Role::findByName('Soporte', 'web');
        $this->assertEqualsCanonicalizing(['usuarios.ver', 'usuarios.suspender'], $role->permissions->pluck('name')->all());

        // Los permisos exclusivos y los nombres del sistema no se aceptan.
        $this->actingAs($admin)->post(route('admin.roles.store'), ['name' => 'Correo', 'permissions' => ['correo.configurar']])->assertSessionHasErrors('permissions.0');
        $this->actingAs($admin)->post(route('admin.roles.store'), ['name' => 'Admin', 'permissions' => ['usuarios.ver']])->assertSessionHasErrors('name');
        $this->actingAs($admin)->get(route('admin.roles.edit', Role::findByName('Admin', 'web')))->assertNotFound();

        $this->student()->assignRole('Soporte');
        $this->actingAs($admin)->delete(route('admin.roles.destroy', $role))->assertSessionHas('error');
        $this->assertNotNull(Role::where('name', 'Soporte')->first());

        $this->actingAs($this->delegated(['usuarios.ver', 'roles.asignar'], 'Otro'))->post(route('admin.roles.store'), ['name' => 'Nuevo', 'permissions' => ['usuarios.ver']])->assertForbidden();
    }

    public function test_deleting_requires_the_exact_email_and_is_logged(): void
    {
        $admin = $this->superAdmin();
        $student = $this->student();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $student), ['confirm_email' => 'otro@example.com'])->assertSessionHasErrors('confirm_email');
        $this->assertNotNull($student->fresh());

        $this->actingAs($admin)->delete(route('admin.users.destroy', $student), ['confirm_email' => $student->email])->assertRedirect(route('admin.users.index'));
        $this->assertNull($student->fresh());
        $this->assertSame($student->email, Activity::where('description', 'Eliminó la cuenta definitivamente')->first()->properties['correo']);
    }

    public function test_audit_log_lists_actions_without_secrets(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->put(route('admin.mail-settings.update'), [
            'enabled' => '1', 'host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls',
            'username' => 'cuenta-smtp', 'password' => 'contraseña-secreta', 'from_address' => 'no-reply@example.com', 'from_name' => 'Aprende Guate',
        ]);

        $entry = Activity::where('log_name', 'configuracion')->first();
        $this->assertNotNull($entry);
        $this->assertStringNotContainsString('contraseña-secreta', json_encode($entry->properties));
        $this->assertStringNotContainsString('cuenta-smtp', json_encode($entry->properties));
        $this->actingAs($admin)->get(route('admin.audit.index', ['categoria' => 'configuracion']))->assertOk()->assertSee('actualizó el correo saliente');
    }

    public function test_two_factor_can_be_required_for_admin_access(): void
    {
        config(['admin.require_two_factor' => true]);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('profile.edit', ['section' => 'security']));
    }

    public function test_console_command_recovers_a_super_admin(): void
    {
        $user = $this->student();

        $this->artisan('admin:super', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->isSuperAdmin());
        $this->artisan('admin:super', ['email' => $user->email, '--quitar' => true])->assertFailed();
    }

    public function test_workspace_switch_follows_admin_permissions(): void
    {
        $support = $this->delegated(['usuarios.ver']);

        $this->actingAs($support)->post(route('workspace.switch'), ['space' => 'admin'])->assertRedirect(route('admin.dashboard'));
        $this->actingAs($support)->post(route('workspace.switch'), ['space' => 'mentor'])->assertForbidden();
        $this->actingAs($this->student())->post(route('workspace.switch'), ['space' => 'admin'])->assertForbidden();
        $this->assertSame([], array_diff(AdminAccess::delegable(), $this->superAdmin()->adminPermissions()));
    }
}
