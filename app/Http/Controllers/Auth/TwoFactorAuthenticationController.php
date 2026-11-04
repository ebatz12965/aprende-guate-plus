<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use App\Support\SecurityEvents;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorAuthenticationController extends Controller
{
    /** Intentos fallidos de código por cuenta antes de bloquear el segundo paso. */
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 900;

    /** Minutos durante los que se puede descargar el PDF de los códigos recién generados. */
    private const RECOVERY_DOWNLOAD_MINUTES = 15;

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

        $counter = $twoFactor->matchingCounter($secret, $data['code']);
        if ($counter === null) {
            return back()->withErrors(['code' => 'El código no es válido. Revisa la hora de tu teléfono e inténtalo de nuevo.']);
        }

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_counter' => $counter,
        ])->save();

        $request->session()->forget('two_factor.setup_secret');
        $this->issueRecoveryCodes($request, $twoFactor);

        return redirect()->route('profile.edit')->with('success', 'La verificación en dos pasos fue activada. Guarda tus códigos de recuperación en un lugar seguro.');
    }

    /** Genera códigos de recuperación nuevos; los anteriores dejan de servir. */
    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        abort_unless($request->user()->hasTwoFactorEnabled(), 403);
        $request->validateWithBag('recoveryCodes', ['current_password' => ['required', 'current_password']]);

        $this->issueRecoveryCodes($request, $twoFactor);

        return redirect()->route('profile.edit', ['section' => 'security'])->with('success', 'Generamos códigos de recuperación nuevos. Los anteriores ya no sirven.');
    }

    /** PDF con los códigos recién generados; solo existe durante unos minutos después de generarlos. */
    public function downloadRecoveryCodes(Request $request): Response
    {
        $pending = $request->session()->get('two_factor.recovery_codes_download');
        abort_unless(is_array($pending) && ($pending['expires_at'] ?? 0) > now()->timestamp, 404);

        $pdf = Pdf::loadView('profile.recovery-codes-pdf', [
            'user' => $request->user(),
            'codes' => $pending['codes'],
            'generatedAt' => now()->setTimestamp($pending['generated_at']),
        ])->setPaper('a4')->setOption(['dpi' => 144, 'defaultFont' => 'Figtree', 'isFontSubsettingEnabled' => true]);

        return $pdf->download('codigos-de-recuperacion-aprende-guate.pdf');
    }

    /** Guarda solo los hashes; el texto se muestra una vez y queda disponible para el PDF durante 15 minutos. */
    private function issueRecoveryCodes(Request $request, TwoFactorAuthenticationService $twoFactor): void
    {
        $codes = $twoFactor->recoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $codes)])->save();

        $request->session()->flash('two_factor.recovery_codes', $codes);
        $request->session()->put('two_factor.recovery_codes_download', [
            'codes' => $codes,
            'generated_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(self::RECOVERY_DOWNLOAD_MINUTES)->timestamp,
        ]);
    }

    public function disable(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'max:20'],
        ]);
        $user = $request->user();
        $isAuthenticatorCode = $twoFactor->verifyForUser($user, $data['code']);

        if (! $isAuthenticatorCode && ! $twoFactor->consumeRecoveryCode($user, $data['code'])) {
            return back()->withErrors(['two_factor_code' => 'El código de autenticación o recuperación no es válido.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_reminder_shown_at' => null,
            'two_factor_last_counter' => null,
        ])->save();
        $request->session()->forget('two_factor.recovery_codes_download');

        return redirect()->route('profile.edit')->with('success', 'La verificación en dos pasos fue desactivada. Te recomendamos activarla nuevamente cuando puedas.');
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = User::find($request->session()->get('two_factor.login_user_id'));
        if (! $user) {
            return redirect()->route('login');
        }

        // Igual que el inicio de sesión: la página inicial con el segundo paso en una ventana flotante.
        return view('welcome', ['challengeAccount' => [
            'name' => $user->name,
            'initial' => mb_strtoupper(mb_substr($user->name, 0, 1)),
            'email' => $this->maskEmail($user->email),
        ]]);
    }

    /** «No soy yo»: descarta el segundo paso pendiente y vuelve al inicio de sesión. */
    public function cancelChallenge(Request $request): RedirectResponse
    {
        $request->session()->forget(['two_factor.login_user_id', 'two_factor.remember']);

        return redirect()->route('login');
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

        // Además del límite por IP de la ruta, cada cuenta tiene su propio límite: cambiar de IP no da más intentos.
        $limiterKey = 'two-factor-challenge:'.$user->id;
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);
            SecurityEvents::lockout('two_factor', $user->email, $request->ip());

            return back()->with('two_factor_locked', true)->withErrors(['code' => "Demasiados intentos fallidos. Espera {$minutes} minuto(s) e inténtalo de nuevo."]);
        }

        $isAuthenticatorCode = $twoFactor->verifyForUser($user, $data['code']);
        if (! $isAuthenticatorCode && ! $twoFactor->consumeRecoveryCode($user, $data['code'])) {
            RateLimiter::hit($limiterKey, self::LOCKOUT_SECONDS);
            SecurityEvents::failedTwoFactor($user, $request->ip());
            $remaining = RateLimiter::remaining($limiterKey, self::MAX_ATTEMPTS);
            if ($remaining === 0) {
                SecurityEvents::lockout('two_factor', $user->email, $request->ip());

                return back()->with('two_factor_locked', true)->withErrors(['code' => 'Demasiados intentos fallidos. Espera '.(int) ceil(self::LOCKOUT_SECONDS / 60).' minuto(s) e inténtalo de nuevo.']);
            }

            return back()->with('two_factor_remaining', $remaining)->withErrors(['code' => 'El código no es válido. Inténtalo nuevamente.']);
        }
        RateLimiter::clear($limiterKey);

        Auth::login($user, (bool) $request->session()->pull('two_factor.remember', false));
        $request->session()->forget('two_factor.login_user_id');
        $request->session()->regenerate();

        return app(AuthenticatedSessionController::class)->completeLogin($request, $user);
    }

    /** Muestra solo lo necesario para reconocer la cuenta: a•••••@gmail.com. */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).str_repeat('•', max(mb_strlen($local) - 1, 3)).($domain !== '' ? '@'.$domain : '');
    }
}
