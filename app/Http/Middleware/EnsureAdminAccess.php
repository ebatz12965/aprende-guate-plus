<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Entrada al panel: super administrador o al menos un permiso delegado; con 2FA si así se configura. */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->canAccessAdmin(), 403);

        if (config('admin.require_two_factor') && ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit', ['section' => 'security'])
                ->with('error', 'Para entrar a la administración necesitas activar la verificación en dos pasos.');
        }

        return $next($request);
    }
}
