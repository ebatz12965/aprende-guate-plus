<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Correcciones de las pruebas OWASP Top 10:2025 con ZAP (noviembre de 2026). */
class OwaspHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_restricts_every_resource_type_without_wildcards(): void
    {
        config(['app.debug' => false]);
        $policy = (string) $this->get('/')->headers->get('Content-Security-Policy');

        foreach (["default-src 'self'", "font-src 'self'", "img-src 'self' data: blob:", "frame-src 'self' https://challenges.cloudflare.com", "object-src 'none'"] as $directive) {
            $this->assertStringContainsString($directive, $policy);
        }
        $this->assertDoesNotMatchRegularExpression('/\s\*(\s|;|$)/', $policy, 'La política no debe tener comodines.');
        $this->assertStringNotContainsString('https:;', $policy, 'Sin anuncios no se permiten imágenes de cualquier dominio.');
    }

    public function test_pages_use_self_hosted_fonts_and_no_external_stylesheets(): void
    {
        $html = (string) $this->get('/')->getContent();

        $this->assertStringNotContainsString('fonts.bunny.net', $html);
        $this->assertDoesNotMatchRegularExpression('/<link[^>]+rel="stylesheet"[^>]+href="https?:\/\/(?!localhost)/', $html);
    }

    public function test_no_readable_csrf_cookie_is_sent_but_csrf_protection_still_applies(): void
    {
        $response = $this->get('/login');

        $this->assertNull(collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'XSRF-TOKEN'));
        $this->assertFalse($response->headers->has('X-Powered-By'));

        // Con el middleware real de CSRF (fuera de la exención de pruebas), un POST sin token se rechaza.
        $this->app->instance('env', 'production');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('notifications.read-all'))->assertStatus(419);
    }

    public function test_redirects_carry_no_body(): void
    {
        $response = $this->post('/login', ['email' => 'nadie@example.com', 'password' => 'x'])->assertRedirect();

        $this->assertSame('', $response->getContent());
    }

    public function test_non_numeric_phase_ids_return_not_found_instead_of_an_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/mi-ruta/fases/abc')->assertNotFound();
        $this->actingAs($user)->post('/mi-ruta/fases/abc/quiz')->assertNotFound();
    }

    public function test_failed_logins_are_recorded_without_the_password(): void
    {
        $user = User::factory()->create(['email' => 'persona@example.com']);

        $this->post('/login', ['email' => 'Persona@example.com', 'password' => 'Clave-equivocada-123']);
        $this->post('/login', ['email' => 'no-existe@example.com', 'password' => 'Otra-clave-456']);

        $entries = Activity::query()->where('log_name', 'acceso')->where('description', 'Inicio de sesión fallido')->get();
        $this->assertCount(2, $entries);
        $this->assertSame('persona@example.com', $entries[0]->properties['correo']);
        $this->assertTrue($entries[0]->subject->is($user));
        $this->assertNull($entries[1]->subject);
        $this->assertStringNotContainsString('Clave-equivocada', $entries->toJson());
    }

    public function test_a_lockout_is_logged_once_and_alerts_super_administrators(): void
    {
        Notification::fake();
        $super = User::factory()->create();
        $super->assignRole(Role::findOrCreate(AdminAccess::SUPER_ROLE));
        $student = User::factory()->create();

        for ($i = 0; $i < 8; $i++) {
            $this->post('/login', ['email' => 'objetivo@example.com', 'password' => "Equivocada-{$i}"]);
        }

        $this->assertSame(1, Activity::query()->where('description', 'Bloqueo temporal por intentos fallidos de inicio de sesión')->count());
        Notification::assertSentToTimes($super, PlatformAlertNotification::class, 1);
        Notification::assertNotSentTo($student, PlatformAlertNotification::class);
    }
}
