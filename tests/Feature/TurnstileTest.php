<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile' => ['site_key' => '0xSITIO-DE-PRUEBA', 'secret' => 'secreto-de-prueba', 'hostnames' => ['aprendeguate.com']]]);
    }

    private function cloudflare(array $body): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response($body)]);
    }

    private function user(): User
    {
        return User::factory()->create(['email' => 'persona@example.com', 'password' => 'ClaveSegura2026']);
    }

    public function test_the_forms_render_the_widget_with_its_action_and_the_script_with_the_nonce(): void
    {
        foreach (['/login' => 'login', '/register' => 'register', '/forgot-password' => 'password_reset'] as $url => $action) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('data-sitekey="0xSITIO-DE-PRUEBA"', $html, $url);
            $this->assertStringContainsString('data-action="'.$action.'"', $html, $url);
            $this->assertMatchesRegularExpression('/<script nonce="[^"]+" src="https:\/\/challenges\.cloudflare\.com\/turnstile\/v0\/api\.js\?render=explicit/', $html, $url);
        }
    }

    public function test_login_passes_with_a_valid_token_and_sends_secret_token_and_ip(): void
    {
        $this->user();
        $this->cloudflare(['success' => true, 'action' => 'login', 'hostname' => 'aprendeguate.com']);

        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'ClaveSegura2026', 'cf-turnstile-response' => 'token-valido'])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        Http::assertSent(fn (Request $r) => $r->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $r['secret'] === 'secreto-de-prueba' && $r['response'] === 'token-valido' && $r['remoteip'] === '127.0.0.1');
    }

    public function test_login_is_rejected_without_token_or_with_a_reused_token(): void
    {
        $this->user();
        $this->cloudflare(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);

        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'ClaveSegura2026'])
            ->assertSessionHasErrors(['cf-turnstile-response' => 'Completa la verificación de seguridad antes de continuar.']);
        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'ClaveSegura2026', 'cf-turnstile-response' => 'token-usado'])
            ->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_a_token_from_another_form_or_domain_is_rejected(): void
    {
        $this->user();
        $this->cloudflare(['success' => true, 'action' => 'register', 'hostname' => 'aprendeguate.com']);
        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'ClaveSegura2026', 'cf-turnstile-response' => 'token'])->assertSessionHasErrors('cf-turnstile-response');

        $this->cloudflare(['success' => true, 'action' => 'login', 'hostname' => 'sitio-falso.com']);
        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'ClaveSegura2026', 'cf-turnstile-response' => 'token'])->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_if_cloudflare_is_down_login_still_works_but_registration_does_not(): void
    {
        Notification::fake();
        $this->user();
        Http::fake(fn () => throw new ConnectionException('sin conexión'));

        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'ClaveSegura2026', 'cf-turnstile-response' => 'token'])->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        auth()->logout();

        $this->post('/register', ['name' => 'Nueva', 'email' => 'nueva@example.com', 'password' => 'ClaveSegura2026', 'password_confirmation' => 'ClaveSegura2026', 'cf-turnstile-response' => 'token'])
            ->assertSessionHasErrors(['cf-turnstile-response' => 'No pudimos completar la verificación de seguridad en este momento. Inténtalo de nuevo en unos minutos.']);
        $this->assertDatabaseMissing('users', ['email' => 'nueva@example.com']);
    }

    public function test_registration_and_password_reset_pass_with_their_own_action(): void
    {
        Notification::fake();
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
            ->push(['success' => true, 'action' => 'register', 'hostname' => 'aprendeguate.com'])
            ->push(['success' => true, 'action' => 'password_reset', 'hostname' => 'aprendeguate.com'])]);
        $this->post('/register', ['name' => 'Nueva', 'email' => 'nueva@example.com', 'password' => 'ClaveSegura2026', 'password_confirmation' => 'ClaveSegura2026', 'cf-turnstile-response' => 'token'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'nueva@example.com']);
        auth()->logout();

        $this->post('/forgot-password', ['email' => 'nueva@example.com', 'cf-turnstile-response' => 'token'])->assertSessionHasNoErrors()->assertSessionHas('status');
    }

    public function test_without_secret_key_the_verification_is_off(): void
    {
        config(['services.turnstile.secret' => null]);
        $this->user();

        $this->assertStringNotContainsString('data-sitekey', $this->get('/login')->getContent());
        $this->post('/login', ['email' => 'persona@example.com', 'password' => 'ClaveSegura2026'])->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }
}
