<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Una cuenta con contraseña temporal solo puede cambiarla (o cerrar sesión) hasta que la cambie. */
class EnsurePasswordChanged
{
    /** Lo único permitido mientras la contraseña sea temporal. */
    private const ALLOWED = ['password.change', 'password.change.update', 'logout', 'session.ping', 'session.expire', 'verification.*', 'two-factor.*'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->password_change_required || $request->routeIs(...self::ALLOWED)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Cambia tu contraseña temporal para continuar.'], 409);
        }

        return redirect()->route('password.change');
    }
}
