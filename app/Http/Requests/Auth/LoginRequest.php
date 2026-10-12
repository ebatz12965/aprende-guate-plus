<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\TurnstileToken;
use App\Support\Audit;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            // Si Cloudflare no responde, el acceso sigue funcionando (ya hay límite de intentos).
            ...TurnstileToken::rules('login', allowWhenUnavailable: true),
        ];
    }

    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::validate($this->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = User::query()->where('email', $this->input('email'))->firstOrFail();
        // Se informa solo después de validar la contraseña, para no revelar qué correos existen.
        if ($user->isSuspended()) {
            Audit::log('acceso', 'Intento de inicio de sesión con la cuenta suspendida', $user, [], $user);
            throw ValidationException::withMessages(['email' => $user->suspensionMessage()]);
        }

        return $user;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
