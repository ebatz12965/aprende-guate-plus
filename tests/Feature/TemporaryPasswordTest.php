<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemporaryPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function temporaryPassword(string $email): string
    {
        Artisan::call('users:create-admin', ['email' => $email]);
        preg_match('/se muestra solo esta vez\): (\S+)/', Artisan::output(), $match);

        return $match[1];
    }

    public function test_the_command_creates_a_super_admin_with_a_temporary_password(): void
    {
        $password = $this->temporaryPassword('Ebatzm@miumg.edu.gt');

        $user = User::where('email', 'ebatzm@miumg.edu.gt')->sole();
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->hasRole('Student'));
        $this->assertTrue($user->password_change_required);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertGreaterThanOrEqual(15, strlen($password));

        // Volver a ejecutarlo no la sobrescribe sin --reset.
        $this->artisan('users:create-admin', ['email' => 'ebatzm@miumg.edu.gt'])->assertFailed();
        $this->artisan('users:create-admin', ['email' => 'ebatzm@miumg.edu.gt', '--reset' => true])->assertSuccessful();
        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }

    public function test_the_first_login_requires_changing_the_temporary_password(): void
    {
        $password = $this->temporaryPassword('ebatzm@miumg.edu.gt');

        $this->post('/login', ['email' => 'ebatzm@miumg.edu.gt', 'password' => $password]);
        $this->assertAuthenticated();
        // Cualquier página lleva al cambio de contraseña, también la administración.
        $this->get(route('admin.dashboard'))->assertRedirect(route('password.change'));
        $this->get(route('adaptive.dashboard'))->assertRedirect(route('password.change'));
        $this->getJson(route('mentorship.widget'))->assertStatus(409);
        $this->get(route('password.change'))->assertOk()->assertSee('Crea tu contraseña');

        $this->put(route('password.change.update'), ['current_password' => 'incorrecta', 'password' => 'Nueva-clave-segura-2026', 'password_confirmation' => 'Nueva-clave-segura-2026'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('password.change.update'), ['current_password' => $password, 'password' => $password, 'password_confirmation' => $password])
            ->assertSessionHasErrors('password');

        $this->put(route('password.change.update'), ['current_password' => $password, 'password' => 'Nueva-clave-segura-2026', 'password_confirmation' => 'Nueva-clave-segura-2026'])
            ->assertRedirect(route('dashboard'));
        $user = User::where('email', 'ebatzm@miumg.edu.gt')->sole();
        $this->assertFalse($user->password_change_required);
        $this->assertTrue(Hash::check('Nueva-clave-segura-2026', $user->password));
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('password.change'))->assertRedirect(route('dashboard'));
    }

    public function test_regular_accounts_are_not_affected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('password.change'))->assertRedirect(route('dashboard'));
    }
}
