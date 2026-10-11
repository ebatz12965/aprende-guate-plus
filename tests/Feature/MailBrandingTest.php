<?php

namespace Tests\Feature;

use App\Mail\MailSettingsTestMail;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function assertBranded(string $html): void
    {
        $this->assertStringContainsString('aprende-guate-email-logo.png', $html);
        $this->assertStringContainsString('Aprende Guate', $html);
        $this->assertStringContainsString('El equipo de Aprende Guate', $html);
        $this->assertStringContainsString('Plataforma educativa de Guatemala', $html);
        // Ni el logo ni el nombre de Laravel, ni textos en inglés de la plantilla.
        $this->assertStringNotContainsString('Laravel', $html);
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
    }

    public function test_system_emails_carry_the_aprende_guate_brand_even_if_app_name_is_laravel(): void
    {
        // Así estaba el servidor: APP_NAME=Laravel.
        config(['app.name' => 'Laravel']);
        $user = User::factory()->create(['name' => 'María López']);

        $this->assertBranded((string) (new ResetPasswordNotification('token'))->toMail($user)->render());
        $this->assertBranded((string) (new VerifyEmailNotification)->toMail($user)->render());
        $this->assertBranded((string) (new PlatformAlertNotification(category: 'platform', title: 'Aviso', body: 'Tu solicitud fue aprobada.'))->toMail($user)->render());
        $this->assertBranded((new MailSettingsTestMail('Edwing Batz', 'smtp.ejemplo.com'))->render());
    }

    public function test_the_mail_settings_test_uses_the_branded_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['name' => 'Edwing Batz']);
        $admin->assignRole('Admin');
        foreach (['mail_enabled' => '1', 'mail_host' => 'smtp.ejemplo.com', 'mail_port' => '587', 'mail_from_address' => 'no-reply@aprendeguate.com', 'mail_from_name' => 'Aprende Guate'] as $key => $value) {
            PlatformSetting::setString($key, $value);
        }

        $this->actingAs($admin)->post(route('admin.mail-settings.test'), ['test_recipient' => 'destino@example.com'])->assertSessionHas('success');

        Mail::assertSent(MailSettingsTestMail::class, fn (MailSettingsTestMail $mail) => $mail->hasTo('destino@example.com') && $mail->host === 'smtp.ejemplo.com' && $mail->sentBy === 'Edwing Batz');
    }
}
