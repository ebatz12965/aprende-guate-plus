<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ErrorReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Como en el servidor: sin detalles técnicos en pantalla.
        config(['app.debug' => false]);
        ErrorReference::reset();
        Route::middleware('web')->group(function () {
            Route::get('/__prueba/403-propio', fn () => abort(403, 'Completa el mini-quiz anterior para desbloquear esta fase.'));
            Route::get('/__prueba/403-generico', fn () => abort(403));
            Route::get('/__prueba/419', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
            Route::get('/__prueba/429', fn () => throw new ThrottleRequestsException('Too Many Attempts.', null, ['Retry-After' => 90]));
            Route::get('/__prueba/500', fn () => throw new RuntimeException('Fallo interno con detalles técnicos: SQLSTATE[HY000]'));
            Route::get('/__prueba/503', fn () => abort(503));
            Route::get('/__prueba/418', fn () => abort(418));
        });
    }

    public function test_the_404_page_is_in_spanish_and_branded(): void
    {
        $this->get('/esta-pagina-no-existe')->assertNotFound()
            ->assertSee('No encontramos esta página')->assertSee('ERROR 404')->assertSee('Aprende Guate')
            ->assertSee('Ir al inicio')->assertDontSee('Not Found');
    }

    public function test_403_shows_our_own_message_or_a_spanish_default(): void
    {
        $this->get('/__prueba/403-propio')->assertForbidden()->assertSee('Completa el mini-quiz anterior para desbloquear esta fase.');
        $this->get('/__prueba/403-generico')->assertForbidden()->assertSee('No tienes acceso a esta página')->assertDontSee('Forbidden');
    }

    public function test_500_hides_technical_details_and_shows_a_reference_code(): void
    {
        $response = $this->get('/__prueba/500')->assertStatus(500)
            ->assertSee('Algo salió mal de nuestro lado')->assertDontSee('SQLSTATE')->assertDontSee('RuntimeException');
        $this->assertMatchesRegularExpression('/Código del error: <strong>[2-9A-HJ-NP-Z]{8}<\/strong>/', $response->getContent());
        $response->assertSee(ErrorReference::get());
    }

    public function test_the_other_errors_have_their_own_page(): void
    {
        $this->get('/__prueba/419')->assertStatus(419)->assertSee('La página expiró')->assertSee('Recargar la página');
        $this->get('/__prueba/429')->assertStatus(429)->assertSee('Vas muy rápido')->assertSee('Podrás intentarlo de nuevo en 90 segundos.');
        $this->get('/__prueba/503')->assertStatus(503)->assertSee('Estamos en mantenimiento');
        // Cualquier otro código 4xx usa la página general en español.
        $this->get('/__prueba/418')->assertStatus(418)->assertSee('No se pudo completar la solicitud')->assertSee('ERROR 418');
    }

    public function test_json_errors_are_in_spanish_without_internal_details(): void
    {
        $this->getJson('/esta-ruta-no-existe')->assertNotFound()->assertExactJson(['message' => 'No encontramos lo que buscas.']);
        $this->getJson('/__prueba/403-propio')->assertForbidden()->assertJsonPath('message', 'Completa el mini-quiz anterior para desbloquear esta fase.');
        $this->getJson('/__prueba/419')->assertStatus(419)->assertJsonPath('message', 'La página expiró. Recárgala e inténtalo de nuevo.');
        $this->getJson(route('adaptive.dashboard'))->assertUnauthorized()->assertJsonPath('message', 'Tu sesión terminó. Vuelve a iniciar sesión.');

        $json = $this->getJson('/__prueba/500')->assertStatus(500)->json();
        $this->assertStringNotContainsString('SQLSTATE', json_encode($json));
        $this->assertSame(ErrorReference::get(), $json['reference']);
        $this->assertStringContainsString('comparte este código con soporte', $json['message']);
    }

    public function test_forgot_password_is_in_spanish_and_does_not_reveal_registered_emails(): void
    {
        Notification::fake();
        $this->get(route('password.request'))->assertOk()
            ->assertSee('¿Olvidaste tu contraseña? No te preocupes.')->assertSee('Enviarme el enlace para restablecerla')
            ->assertDontSee('Forgot your password')->assertDontSee('Email Password Reset Link');

        $user = User::factory()->create(['email' => 'registrada@example.com']);
        $registered = $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status')->getSession()->get('status');
        $unknown = $this->post(route('password.email'), ['email' => 'no-existe@example.com'])->assertSessionHasNoErrors()->assertSessionHas('status')->getSession()->get('status');

        // Mismo mensaje exista o no la cuenta.
        $this->assertSame($registered, $unknown);
        $this->assertStringStartsWith('Si ese correo está registrado', $unknown);
        $this->post(route('password.email'), ['email' => ''])->assertSessionHasErrors(['email' => 'El campo correo electrónico es obligatorio.']);
    }

    public function test_access_screens_have_no_english_left(): void
    {
        foreach ([route('register'), route('password.request')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            foreach (['Sign up', 'Already registered', 'Forgot your password', 'Email Password Reset Link', 'Register</'] as $english) {
                $this->assertStringNotContainsString($english, $html, "«{$english}» sigue en {$url}");
            }
        }
        $this->get(route('register'))->assertSee('Registrarme con Google')->assertSee('¿Ya tienes una cuenta?');
    }

    public function test_validation_uses_spanish_field_names(): void
    {
        $this->assertSame('El campo respuesta es obligatorio.', validator(['answer' => ''], ['answer' => 'required'])->errors()->first('answer'));
        $this->assertSame('El campo archivo adjunto es obligatorio.', validator([], ['attachment' => 'required'])->errors()->first('attachment'));
    }
}
