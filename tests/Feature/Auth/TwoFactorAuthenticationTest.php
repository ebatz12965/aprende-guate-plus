<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_without_two_factor_shows_security_reminder(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('two_factor_reminder', true);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->two_factor_reminder_shown_at);
    }

    public function test_two_factor_user_must_complete_challenge_before_login(): void
    {
        $user = User::factory()->create();
        $secret = 'JBSWY3DPEHPK3PXP';
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => [],
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge', absolute: false));
        $this->assertGuest();

        $service = app(TwoFactorAuthenticationService::class);
        $method = new ReflectionMethod($service, 'codeForCounter');
        $code = $method->invoke($service, $secret, intdiv(time(), 30));

        $response = $this->post(route('two-factor.challenge.verify'), ['code' => $code]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }
}
