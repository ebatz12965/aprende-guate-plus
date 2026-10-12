<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_and_redirects_to_the_verification_notice(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Cuenta por correo',
            'email' => 'correo@ejemplo.test',
            'password' => 'ClaveSegura2026',
            'password_confirmation' => 'ClaveSegura2026',
        ]);

        $user = User::where('email', 'correo@ejemplo.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_email_account_cannot_continue_after_login(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'password']);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_uses_the_branded_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }
}
