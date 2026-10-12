<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile: comprueba que quien envía un formulario es una persona.
 *
 * Sin clave del sitio o sin clave secreta queda desactivado (los formularios funcionan igual), para poder
 * desplegar el código antes de escribir la clave secreta en el servidor.
 */
class Turnstile
{
    public const VALID = 'valid';

    public const INVALID = 'invalid';

    public const UNAVAILABLE = 'unavailable';

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** Claves secretas de prueba publicadas por Cloudflare: responden con un hostname genérico. */
    private const TEST_SECRETS = ['1x0000000000000000000000000000000AA', '2x0000000000000000000000000000000AA', '3x0000000000000000000000000000000AA'];

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret'));
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    /** VALID, INVALID (token falso, vencido, reutilizado o de otro sitio/acción) o UNAVAILABLE (Cloudflare no respondió). */
    public static function verify(?string $token, string $action, ?string $ip): string
    {
        if (! is_string($token) || $token === '' || strlen($token) > 2048) {
            return self::INVALID;
        }

        $secret = (string) config('services.turnstile.secret');
        try {
            $response = Http::asForm()->timeout(10)->post(self::VERIFY_URL, array_filter(['secret' => $secret, 'response' => $token, 'remoteip' => $ip]));
        } catch (\Throwable $exception) {
            Log::warning('turnstile.unavailable', ['action' => $action, 'reason' => $exception->getMessage()]);

            return self::UNAVAILABLE;
        }

        if (! $response->successful()) {
            Log::warning('turnstile.unavailable', ['action' => $action, 'status' => $response->status()]);

            return self::UNAVAILABLE;
        }

        $data = $response->json() ?? [];
        if (($data['success'] ?? false) !== true) {
            Log::info('turnstile.rejected', ['action' => $action, 'errors' => $data['error-codes'] ?? []]);

            return self::INVALID;
        }

        // Con las claves de prueba Cloudflare no devuelve la acción ni el dominio reales.
        if (in_array($secret, self::TEST_SECRETS, true)) {
            return self::VALID;
        }

        $hostnames = (array) config('services.turnstile.hostnames');
        if (($data['action'] ?? null) !== $action || ! in_array($data['hostname'] ?? null, $hostnames, true)) {
            Log::warning('turnstile.mismatch', ['expected_action' => $action, 'action' => $data['action'] ?? null, 'hostname' => $data['hostname'] ?? null]);

            return self::INVALID;
        }

        return self::VALID;
    }
}
