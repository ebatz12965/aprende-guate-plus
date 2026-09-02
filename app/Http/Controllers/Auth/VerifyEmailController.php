<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Verify the signed email confirmation link for the authenticated account.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! URL::hasValidSignature($request)) {
            return redirect()->route('verification.notice')->with(
                'error',
                'No pudimos confirmar tu correo porque el enlace es inválido o ya venció. Solicita uno nuevo e inténtalo otra vez.',
            );
        }

        $requestedUserId = (string) $request->route('id');
        $requestedHash = (string) $request->route('hash');

        if ($requestedUserId !== (string) $user->getKey() || ! hash_equals(sha1($user->getEmailForVerification()), $requestedHash)) {
            return redirect()->route('verification.notice')->with(
                'error',
                'Ese enlace no corresponde a la cuenta con la que iniciaste sesión. Revisa el correo correcto o solicita un nuevo enlace.',
            );
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false))->with(
                'success',
                'Tu correo ya había sido confirmado. Puedes continuar en la plataforma.',
            );
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            return redirect()->intended(route('dashboard', absolute: false))->with(
                'success',
                '¡Correo confirmado correctamente! Tu cuenta ya está activa y puedes comenzar a aprender.',
            );
        }

        return redirect()->route('verification.notice')->with(
            'error',
            'No pudimos confirmar tu correo en este momento. Intenta nuevamente o solicita un nuevo enlace.',
        );
    }
}
