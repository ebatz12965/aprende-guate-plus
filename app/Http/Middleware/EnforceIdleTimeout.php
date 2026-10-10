<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión después de un tiempo sin actividad real. Las consultas de fondo se marcan con el encabezado
 * X-Background-Request y no reinician el contador; si no, una página abierta mantendría la sesión para siempre.
 */
class EnforceIdleTimeout
{
    public const BACKGROUND_HEADER = 'X-Background-Request';

    private const KEY = 'idle.last_activity';

    public static function limitFor(User $user): int
    {
        return $user->canAccessAdmin() ? (int) config('idle.admin_minutes') : (int) config('idle.minutes');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $last = $request->session()->get(self::KEY);
        if ($last !== null && now()->timestamp - $last > self::limitFor($user) * 60) {
            $minutes = self::limitFor($user);
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $message = "Cerramos tu sesión porque pasaron más de {$minutes} minutos sin actividad.";

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'idle' => true], 401)
                : redirect()->route('login')->with('status', $message);
        }

        if (! $request->hasHeader(self::BACKGROUND_HEADER)) {
            $request->session()->put(self::KEY, now()->timestamp);
        }

        return $next($request);
    }
}
