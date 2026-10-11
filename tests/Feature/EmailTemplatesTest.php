<?php

namespace Tests\Feature;

use App\Mail\MailSettingsTestMail;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use App\Notifications\ResetPasswordNotification;
use App\Support\EmailTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['name' => 'Administración de prueba']);
        $admin->assignRole('Admin');

        return $admin;
    }

    public function test_admin_edits_the_password_reset_email_and_it_is_used_when_sending(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.email-templates.index'))->assertOk()
            ->assertSee('Restablecer contraseña')->assertSee('Prueba del correo saliente')->assertSee('Conversación de mentoría finalizada');

        $this->actingAs($admin)->put(route('admin.email-templates.update', 'password_reset'), [
            'subject' => 'Tu nueva contraseña, {nombre}',
            'heading' => '¡Hola, {nombre}!',
            'body' => "Alguien pidió cambiar la contraseña de {correo}.\n\nSi fuiste tú, usa el botón.",
            'action_label' => 'Elegir contraseña',
            'closing' => 'Vence en {minutos} minutos.',
        ])->assertRedirect(route('admin.email-templates.edit', 'password_reset'))->assertSessionHas('success');

        $user = User::factory()->create(['name' => 'María López', 'email' => 'maria@example.com']);
        $mail = (new ResetPasswordNotification('token'))->toMail($user);
        $html = (string) $mail->render();

        $this->assertSame('Tu nueva contraseña, María López', $mail->subject);
        $this->assertStringContainsString('¡Hola, María López!', $html);
        $this->assertStringContainsString('Alguien pidió cambiar la contraseña de maria@example.com.', $html);
        $this->assertStringContainsString('Elegir contraseña', $html);
        $this->assertStringContainsString('Vence en 60 minutos.', $html);
        $this->assertStringContainsString('reset-password/token', $html);
        $this->assertStringNotContainsString('{nombre}', $html);
    }

    public function test_restoring_a_template_goes_back_to_the_original_text(): void
    {
        $admin = $this->admin();
        EmailTemplate::create(['key' => 'mail_test', 'subject' => 'Asunto propio', 'body' => 'Texto propio']);

        $this->assertSame('Asunto propio', (new MailSettingsTestMail('Admin', 'smtp.ejemplo.com'))->envelope()->subject);

        $this->actingAs($admin)->delete(route('admin.email-templates.destroy', 'mail_test'))->assertSessionHas('success');

        $this->assertDatabaseMissing('email_templates', ['key' => 'mail_test']);
        $mail = new MailSettingsTestMail('Admin', 'smtp.ejemplo.com');
        $this->assertSame('Prueba de correo · Aprende Guate', $mail->envelope()->subject);
        $this->assertStringContainsString('smtp.ejemplo.com', $mail->render());
    }

    public function test_unknown_placeholders_are_rejected(): void
    {
        $this->actingAs($this->admin())->put(route('admin.email-templates.update', 'mail_test'), [
            'subject' => 'Prueba para {nombre}',
            'body' => 'Servidor: {servidor}',
        ])->assertSessionHasErrors(['subject']);

        $this->assertDatabaseMissing('email_templates', ['key' => 'mail_test']);
    }

    public function test_user_text_is_never_interpreted_as_markup_or_links(): void
    {
        $user = User::factory()->create(['name' => '<b>Ana</b> [clic](https://malo.example)']);
        $html = (string) (new PlatformAlertNotification(category: 'platform', title: 'Aviso', body: 'Hola **mundo** <script>alert(1)</script>'))->toMail($user)->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Ana</b>', $html);
        $this->assertStringNotContainsString('href="https://malo.example"', $html);
        $this->assertStringNotContainsString('<strong>mundo</strong>', $html);
    }

    public function test_preview_renders_the_draft_and_the_test_send_uses_sample_data(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.email-templates.preview', 'mentorship_transcript'), [
            'subject' => 'Borrador', 'heading' => 'Hola, {nombre}', 'body' => 'Tu sesión con {otra_persona} terminó.', 'action_label' => 'Ver', 'closing' => '',
        ])->assertOk()->assertSee('Hola, María López')->assertSee('Tu sesión con Carlos Pérez terminó.')->assertSee('Agrupar las ventas por mes.', false);
        $this->assertDatabaseMissing('email_templates', ['key' => 'mentorship_transcript']);

        $this->actingAs($admin)->post(route('admin.email-templates.test', 'platform_alert'), ['test_recipient' => 'destino@example.com'])
            ->assertSessionHas('error');

        foreach (['mail_enabled' => '1', 'mail_host' => 'smtp.ejemplo.com', 'mail_port' => '587', 'mail_from_address' => 'no-reply@aprendeguate.com', 'mail_from_name' => 'Aprende Guate'] as $key => $value) {
            PlatformSetting::setString($key, $value);
        }
        $this->actingAs($admin)->post(route('admin.email-templates.test', 'platform_alert'), ['test_recipient' => 'destino@example.com'])
            ->assertSessionHas('success');

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('destino@example.com')
            && $mail->email['subject'] === '[Prueba] Recordatorio de mentoría · Aprende Guate');
    }

    public function test_only_people_with_the_mail_permission_can_edit_templates(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.email-templates.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.email-templates.edit', 'no_existe'))->assertNotFound();
    }

    public function test_every_template_renders_with_its_sample_data(): void
    {
        foreach (array_keys(EmailTemplates::definitions()) as $key) {
            $email = EmailTemplates::compose(EmailTemplates::fields($key), EmailTemplates::sampleValues($key), 'https://aprendeguate.com', EmailTemplates::sampleTranscript($key));
            $html = (new TemplatedMail($email))->render();

            $this->assertStringContainsString('El equipo de Aprende Guate', $html, $key);
            $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', $email['subject'].$html, $key);
        }
    }
}
