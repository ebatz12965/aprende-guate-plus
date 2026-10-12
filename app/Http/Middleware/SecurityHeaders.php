<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Un nonce por petición: solo los <script> que lo llevan (los de la app y los de Vite) se ejecutan.
        $nonce = Vite::useCspNonce();
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // El micrófono solo se permite en la etapa de entrevista (llamada de voz); en el resto del sitio sigue bloqueado.
        $microphone = $request->routeIs('adaptive.interviews.stage') ? 'microphone=(self)' : 'microphone=()';
        $response->headers->set('Permissions-Policy', "camera=(), {$microphone}, geolocation=(), payment=()");
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        // Scripts: solo los propios y los que llevan el nonce. Un <script> o un onclick inyectado no se ejecuta.
        // 'unsafe-eval' es necesario porque Alpine.js evalúa sus expresiones (x-data, @click) con Function().
        $scripts = "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://challenges.cloudflare.com".$this->viteDevServer();
        $policy = "frame-ancestors 'none'; base-uri 'self'; object-src 'none'; form-action 'self' https://accounts.google.com; {$scripts}";
        // Con depuración activa (local) la política solo avisa en la consola: la página de error de Laravel usa scripts en línea.
        $response->headers->set(config('app.debug') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $policy);
        if (config('app.debug')) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; object-src 'none'; form-action 'self' https://accounts.google.com");
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /** En desarrollo con `npm run dev`, los scripts llegan desde el servidor de Vite. */
    private function viteDevServer(): string
    {
        if (! Vite::isRunningHot()) {
            return '';
        }
        $url = trim((string) @file_get_contents(public_path('hot')));

        return $url !== '' ? ' '.$url : '';
    }
}
