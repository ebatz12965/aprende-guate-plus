<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TwoFactorAuthenticationController extends Controller
{
    public function begin(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $request->session()->put('two_factor.setup_secret', $twoFactor->generateSecret());

        return redirect()->route('profile.edit', ['section' => 'security', 'setup' => 1])->with('success', 'Agrega la clave a tu aplicación autenticadora y confirma el código de seis dígitos.');
    }

    public function confirm(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $secret = $request->session()->get('two_factor.setup_secret');
        abort_unless(is_string($secret) && $secret !== '', 422, 'Inicia nuevamente la configuración de verificación en dos pasos.');

        if (! $twoFactor->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'El código no es válido. Revisa la hora de tu teléfono e inténtalo de nuevo.']);
        }

        $codes = $twoFactor->recoveryCodes();
        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $codes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor.setup_secret');
        $request->session()->flash('two_factor.recovery_codes', $codes);

        return redirect()->route('profile.edit')->with('success', 'La verificación en dos pasos fue activada. Guarda tus códigos de recuperación en un lugar seguro.');
    }

    public function disable(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'max:20'],
        ]);
        $user = $request->user();
        $isAuthenticatorCode = $user->two_factor_secret && $twoFactor->verify($user->two_factor_secret, $data['code']);

        if (! $isAuthenticatorCode && ! $twoFactor->consumeRecoveryCode($user, $data['code'])) {
            return back()->withErrors(['two_factor_code' => 'El código de autenticación o recuperación no es válido.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_reminder_shown_at' => null,
        ])->save();

        return redirect()->route('profile.edit')->with('success', 'La verificación en dos pasos fue desactivada. Te recomendamos activarla nuevamente cuando puedas.');
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor.login_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $user = User::find($request->session()->get('two_factor.login_user_id'));
        abort_unless($user?->hasTwoFactorEnabled(), 403);
        if ($user->isSuspended()) {
            $request->session()->forget(['two_factor.login_user_id', 'two_factor.remember']);

            return redirect()->route('login')->withErrors(['email' => $user->suspensionMessage()]);
        }

        $isAuthenticatorCode = $twoFactor->verify($user->two_factor_secret, $data['code']);
        if (! $isAuthenticatorCode && ! $twoFactor->consumeRecoveryCode($user, $data['code'])) {
            return back()->withErrors(['code' => 'El código no es válido. Inténtalo nuevamente.']);
        }

        Auth::login($user, (bool) $request->session()->pull('two_factor.remember', false));
        $request->session()->forget('two_factor.login_user_id');
        $request->session()->regenerate();

        return app(AuthenticatedSessionController::class)->completeLogin($request, $user);
    }
}
