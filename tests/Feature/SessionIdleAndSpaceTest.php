<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SessionIdleAndSpaceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(['Admin', 'Student']);

        return $user;
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        return $user;
    }

    public function test_the_last_workspace_is_reopened_after_logging_in_again(): void
    {
        $admin = $this->admin();
        // Sin historial, una cuenta con administración abre el panel administrativo.
        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));

        $this->post(route('workspace.switch'), ['space' => 'learning'])->assertRedirect(route('adaptive.dashboard'));
        $this->assertSame('learning', $admin->fresh()->last_space);
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
        $this->get(route('dashboard'))->assertRedirect(route('adaptive.dashboard'));
    }

    public function test_a_remembered_space_that_is_no_longer_allowed_is_ignored(): void
    {
        $user = $this->student();
        $user->forceFill(['last_space' => 'admin'])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->get(route('dashboard'))->assertRedirect(route('adaptive.dashboard'));
    }

    public function test_the_session_closes_after_the_idle_limit(): void
    {
        config(['idle.minutes' => 30]);
        $this->actingAs($this->student())->get(route('adaptive.dashboard'))->assertOk();

        $this->travel(31)->minutes();
        $this->get(route('adaptive.dashboard'))->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Cerramos tu sesión porque pasaron más de 30 minutos sin actividad.');
        $this->assertGuest();
    }

    public function test_background_polling_does_not_keep_the_session_alive_but_real_activity_does(): void
    {
        config(['idle.minutes' => 30]);
        $user = $this->student();
        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk();

        // Las consultas automáticas no reinician el contador…
        $this->travel(20)->minutes();
        $this->getJson(route('mentorship.widget'), ['X-Background-Request' => '1'])->assertOk();
        $this->travel(11)->minutes();
        $this->getJson(route('mentorship.widget'), ['X-Background-Request' => '1'])->assertStatus(401)->assertJsonPath('idle', true);
        $this->assertGuest();

        // …pero el aviso de actividad del navegador (por ejemplo, mientras lee) sí.
        $this->actingAs($user)->get(route('adaptive.dashboard'));
        $this->travel(20)->minutes();
        $this->postJson(route('session.ping'))->assertOk();
        $this->travel(20)->minutes();
        $this->get(route('adaptive.dashboard'))->assertOk();
    }

    public function test_admin_accounts_have_a_shorter_limit(): void
    {
        config(['idle.minutes' => 30, 'idle.admin_minutes' => 15]);
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();
        $this->travel(16)->minutes();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_browser_can_close_an_idle_session(): void
    {
        $this->actingAs($this->student())->post(route('session.expire'))->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertGuest();
    }

    public function test_remember_me_is_ignored_for_admin_accounts(): void
    {
        $recaller = Auth::guard('web')->getRecallerName();

        $this->post('/login', ['email' => $this->admin()->email, 'password' => 'password', 'remember' => 'on'])->assertCookieMissing($recaller);
        $this->post(route('logout'));
        $this->post('/login', ['email' => $this->student()->email, 'password' => 'password', 'remember' => 'on'])->assertCookie($recaller);
    }

    public function test_pages_include_the_idle_warning(): void
    {
        $this->actingAs($this->student())->get(route('adaptive.dashboard'))->assertSee('¿Sigues ahí?')->assertSee('idleWatch({', false);
    }
}
