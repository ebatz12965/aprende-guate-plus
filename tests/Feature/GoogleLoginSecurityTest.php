<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogle(string $email, bool $verified = true, string $name = 'Persona Google'): void
    {
        $googleUser = (new GoogleUser)->setRaw(['email_verified' => $verified])->map(['id' => '123', 'name' => $name, 'email' => $email]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($googleUser);
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        // Se reemplaza la fábrica completa para que cada llamada use la cuenta de Google más reciente.
        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);
        Socialite::swap($factory);
    }

    public function test_google_login_asks_for_the_two_factor_code_when_it_is_enabled(): void
    {
        $twoFactor = app(TwoFactorAuthenticationService::class);
        $user = User::factory()->create(['email' => 'admin.prueba@example.com']);
        $user->forceFill(['two_factor_secret' => $twoFactor->generateSecret(), 'two_factor_confirmed_at' => now()])->save();
        $this->fakeGoogle('admin.prueba@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame($user->id, session('two_factor.login_user_id'));
    }

    public function test_google_login_without_two_factor_signs_in_directly(): void
    {
        User::factory()->create(['email' => 'estudiante.prueba@example.com']);
        $this->fakeGoogle('estudiante.prueba@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_an_unverified_pre_registered_account_loses_the_intruders_password(): void
    {
        // Un intruso registró el correo de la víctima con una contraseña que conoce, sin poder verificarlo.
        $account = User::factory()->unverified()->create(['email' => 'victima@example.com', 'password' => Hash::make('ClaveDelIntruso1')]);
        $this->fakeGoogle('victima@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $account->refresh();
        $this->assertNotNull($account->email_verified_at);
        $this->assertFalse($account->has_local_password);
        $this->assertFalse(Hash::check('ClaveDelIntruso1', $account->password));
    }

    public function test_google_accounts_with_an_unverified_email_are_rejected(): void
    {
        $this->fakeGoogle('sin-verificar@example.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'sin-verificar@example.com']);
    }

    public function test_accounts_created_with_google_create_a_password_after_confirming_with_google(): void
    {
        $this->fakeGoogle('nueva.google@example.com');
        $this->get('/auth/google/callback');
        $user = User::where('email', 'nueva.google@example.com')->firstOrFail();
        $this->assertFalse($user->has_local_password);

        // Sin confirmar con Google no se puede crear la contraseña.
        $this->actingAs($user)->put(route('password.update'), ['password' => 'NuevaClave2026!', 'password_confirmation' => 'NuevaClave2026!'])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');
        $this->assertFalse($user->fresh()->has_local_password);

        // La cuenta de Google debe coincidir con la de la plataforma.
        $this->fakeGoogle('otra.persona@example.com');
        $this->actingAs($user)->withSession(['socialite.intent' => 'confirm'])->get('/auth/google/callback')->assertSessionHas('error');
        $this->assertNull(session('auth.google_confirmed_at'));

        $this->fakeGoogle('nueva.google@example.com');
        $this->actingAs($user)->withSession(['socialite.intent' => 'confirm'])->get('/auth/google/callback')->assertSessionHas('success');

        $this->actingAs($user)->put(route('password.update'), ['password' => 'NuevaClave2026!', 'password_confirmation' => 'NuevaClave2026!'])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue($user->has_local_password);
        $this->assertTrue(Hash::check('NuevaClave2026!', $user->password));

        // Con contraseña propia ya puede comenzar la configuración de 2FA.
        $this->actingAs($user)->post(route('two-factor.setup'), ['current_password' => 'NuevaClave2026!'])->assertSessionHasNoErrors();
    }

    public function test_the_google_confirmation_expires(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['has_local_password' => false])->save();

        $this->actingAs($user)->withSession(['auth.google_confirmed_at' => now()->subMinutes(11)->timestamp])
            ->put(route('password.update'), ['password' => 'NuevaClave2026!', 'password_confirmation' => 'NuevaClave2026!'])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');
    }
}
