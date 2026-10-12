<?php

namespace App\Rules;

use App\Support\Turnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

/**
 * Valida el token de Turnstile del campo «cf-turnstile-response».
 *
 * Con $allowWhenUnavailable, una caída de Cloudflare no bloquea el formulario (se usa en el inicio de sesión,
 * que ya tiene su propio límite de intentos); en registro y recuperación sí se bloquea, porque envían correos.
 */
class TurnstileToken implements ValidationRule
{
    /** Se evalúa aunque el campo venga vacío: un envío sin token debe fallar. */
    public bool $implicit = true;

    public function __construct(private readonly string $action, private readonly bool $allowWhenUnavailable = false) {}

    public static function rules(string $action, bool $allowWhenUnavailable = false): array
    {
        return Turnstile::enabled() ? ['cf-turnstile-response' => [new self($action, $allowWhenUnavailable)]] : [];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $result = Turnstile::verify(is_string($value) ? $value : null, $this->action, request()->ip());

        if ($result === Turnstile::VALID) {
            return;
        }
        if ($result === Turnstile::UNAVAILABLE && $this->allowWhenUnavailable) {
            Log::warning('turnstile.allowed_while_unavailable', ['action' => $this->action]);

            return;
        }

        $fail($result === Turnstile::UNAVAILABLE
            ? 'No pudimos completar la verificación de seguridad en este momento. Inténtalo de nuevo en unos minutos.'
            : 'Completa la verificación de seguridad antes de continuar.');
    }
}
