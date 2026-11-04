<?php

namespace App\Http\Middleware;

use App\Support\Ads;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Un nonce por petición: solo los <script> que lo llevan (los de la app y los de Vite) se ejecutan.
        $nonce = Vite::useCspNonce();
        $response = $next($request);

        // Las redirecciones no llevan cuerpo: el navegador lo ignora y así no se envía ningún contenido de más.
        if ($response->isRedirection() && ! $response instanceof StreamedResponse && ! $response instanceof BinaryFileResponse) {
            $response->setContent('');
        }

        // PHP anuncia su versión en X-Powered-By si expose_php está activo: no se publica.
        $response->headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // El micrófono solo se permite en la etapa de entrevista (llamada de voz); en el resto del sitio sigue bloqueado.
        $microphone = $request->routeIs('adaptive.interviews.stage') ? 'microphone=(self)' : 'microphone=()';
        $response->headers->set('Permissions-Policy', "camera=(), {$microphone}, geolocation=(), payment=()");
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        // Scripts: solo los propios y los que llevan el nonce. Un <script> o un onclick inyectado no se ejecuta.
        // 'unsafe-eval' es necesario porque Alpine.js evalúa sus expresiones (x-data, @click) con Function().
        $vite = $this->viteDevServer();
        $scripts = "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://challenges.cloudflare.com".$vite;
        // Todo lo que no se nombra queda limitado al propio sitio (default-src): sin comodines.
        // connect-src: la entrevista por voz negocia la llamada con OpenAI desde el navegador.
        $connect = "connect-src 'self' https://api.openai.com https://challenges.cloudflare.com".($vite !== '' ? $vite.' '.str_replace(['http://', 'https://'], ['ws://', 'wss://'], trim($vite)) : '');
        $images = "img-src 'self' data: blob:";
        $frames = "frame-src 'self' https://challenges.cloudflare.com";
        // Los dominios de AdSense solo se permiten en las respuestas que muestran un espacio publicitario.
        if ($request->attributes->get(Ads::REQUEST_FLAG)) {
            $hosts = ' '.implode(' ', Ads::SCRIPT_HOSTS);
            $scripts .= $hosts;
            $connect .= $hosts;
            $frames .= $hosts.' https://googleads.g.doubleclick.net https://tpc.googlesyndication.com https://www.google.com';
            // Los anuncios cargan imágenes desde servidores que cambian; solo en esas páginas se permite cualquier https.
            $images .= ' https:';
        }
        $policy = "frame-ancestors 'none'; base-uri 'self'; object-src 'none'; form-action 'self' https://accounts.google.com; default-src 'self'; {$scripts}; "
            ."style-src 'self' 'unsafe-inline'{$vite}; font-src 'self'; {$images}; media-src 'self' blob:; {$connect}; {$frames}; worker-src 'self' blob:; manifest-src 'self'";
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
