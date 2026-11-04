<?php

use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureModuleAvailable;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\PreventRequestForgery;
use App\Http\Middleware\SecurityHeaders;
use App\Support\ErrorPage;
use App\Support\ErrorReference;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('mentorship:send-reminders')->everyMinute()->withoutOverlapping();
        $schedule->command('mentorship:close-finished')->everyFifteenMinutes()->withoutOverlapping();
        // Solo reencola lo que quedó esperando al evaluador; los errores se reintentan a mano (--include-failed).
        $schedule->command('ai:retry-evaluations')->hourly()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);
        // Los enlaces de los correos (restablecer contraseña, verificar correo) se arman con el Host de la petición:
        // fuera de local solo se aceptan el dominio de APP_URL, sus subdominios y los de TRUSTED_HOSTS.
        $middleware->trustHosts(at: fn () => array_map(fn (string $host) => '^'.preg_quote($host).'$', config('app.trusted_hosts', [])));
        // Guarda la huella de la contraseña en la sesión: al cambiarla o restablecerla se cierran las demás sesiones.
        $middleware->authenticateSessions();
        $middleware->web(append: [EnsureAccountIsActive::class, EnforceIdleTimeout::class, EnsurePasswordChanged::class, EnsureModuleAvailable::class],
            replace: [Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class => PreventRequestForgery::class]);
        $middleware->alias([
            'admin' => EnsureAdminAccess::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El mismo error no se registra dos veces en una petición.
        $exceptions->dontReportDuplicates();

        // Cada error registrado lleva el código que ve la persona en la página de error.
        $exceptions->context(fn () => ['error_ref' => ErrorReference::get()]);

        // Las respuestas JSON (peticiones en segundo plano) también van en español y sin detalles técnicos.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! $response instanceof JsonResponse || $response->getStatusCode() < 400) {
                return $response;
            }
            $data = $response->getData(true);
            $status = $response->getStatusCode();
            $messages = [
                401 => 'Tu sesión terminó. Vuelve a iniciar sesión.',
                403 => 'No tienes permiso para hacer esto.',
                404 => 'No encontramos lo que buscas.',
                405 => 'Esta acción no está permitida aquí.',
                413 => 'El archivo es demasiado grande.',
                419 => 'La página expiró. Recárgala e inténtalo de nuevo.',
                429 => 'Hiciste demasiadas solicitudes. Espera un momento y vuelve a intentarlo.',
                503 => 'La plataforma está en mantenimiento. Vuelve a intentarlo en unos minutos.',
            ];
            // Los mensajes propios (ya en español) se respetan; los genéricos del framework se traducen.
            if ($status >= 500 && ! config('app.debug')) {
                $data = ['message' => 'Ocurrió un error inesperado. Si se repite, comparte este código con soporte: '.ErrorReference::get().'.', 'reference' => ErrorReference::get()];
            } elseif ($status !== 422 && ErrorPage::customMessage(new RuntimeException((string) ($data['message'] ?? ''))) === null) {
                $data['message'] = $messages[$status] ?? 'No se pudo completar la solicitud.';
            }

            return $response->setData($data);
        });
    })->create();
