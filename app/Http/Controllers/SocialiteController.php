<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class SocialiteController extends Controller
{
    /** Minutos que vale la confirmación de identidad con Google para crear una contraseña. */
    public const CONFIRMATION_MINUTES = 10;

    public function redirect(string $provider)
    {
        abort_unless($provider === 'google', 404);
        session()->forget('socialite.intent');

        return Socialite::driver($provider)->redirect();
    }

    /** Cuentas creadas con Google: confirman su identidad con Google antes de crear su contraseña. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->session()->put('socialite.intent', 'confirm');

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->with(['prompt' => 'select_account', 'login_hint' => $request->user()->email])->redirect();
    }

    public static function recentlyConfirmed(Request $request): bool
    {
        $confirmedAt = (int) $request->session()->get('auth.google_confirmed_at', 0);

        return $confirmedAt > 0 && now()->timestamp - $confirmedAt <= self::CONFIRMATION_MINUTES * 60;
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless($provider === 'google', 404);
        $intent = $request->session()->pull('socialite.intent');

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $exception) {
            report($exception);

            return $this->failed($request, $intent, 'No fue posible conectar con Google. Inténtalo nuevamente.');
        }

        // Solo se confía en el correo si Google confirma que pertenece a esa cuenta.
        if (blank($socialUser->getEmail()) || ! $this->emailVerified($socialUser)) {
            return $this->failed($request, $intent, 'Google no confirmó que ese correo te pertenece. Usa otra cuenta o inicia sesión con tu contraseña.');
        }

        if ($intent === 'confirm' && $request->user()) {
            return $this->finishConfirmation($request, $socialUser);
        }

        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $user = User::where('email', Str::lower($socialUser->getEmail()))->first();

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?: Str::before($socialUser->getEmail(), '@'),
                'email' => Str::lower($socialUser->getEmail()),
                'password' => Hash::make(Str::random(40)),
            ]);
            $user->forceFill(['email_verified_at' => now(), 'has_local_password' => false])->save();
        } elseif (! $user->hasVerifiedEmail()) {
            // Alguien pudo registrarse antes con este correo sin poder verificarlo. Al probar Google que el
            // correo es de esta persona, la contraseña y las sesiones de ese registro previo dejan de servir.
            $user->forceFill([
                'password' => Hash::make(Str::random(40)),
                'has_local_password' => false,
                'remember_token' => Str::random(60),
                'email_verified_at' => now(),
            ])->save();
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        if ($user->isSuspended()) {
            return redirect()->route('login')->withErrors(['email' => $user->suspensionMessage()]);
        }

        // Google no reemplaza el segundo paso: con 2FA activo se pide el código igual que con contraseña.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor.login_user_id', $user->id);
            $request->session()->put('two_factor.remember', false);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return app(AuthenticatedSessionController::class)->completeLogin($request, $user);
    }

    private function finishConfirmation(Request $request, SocialUser $socialUser): RedirectResponse
    {
        if (strcasecmp((string) $socialUser->getEmail(), $request->user()->email) !== 0) {
            return redirect()->route('profile.edit', ['section' => 'security'])
                ->with('error', 'La cuenta de Google elegida no coincide con el correo de tu cuenta ('.$request->user()->email.').');
        }

        $request->session()->put('auth.google_confirmed_at', now()->timestamp);
        $request->session()->put('auth.password_confirmed_at', now()->timestamp);

        return redirect()->route('profile.edit', ['section' => 'security'])
            ->with('success', 'Identidad confirmada. Ya puedes crear tu contraseña (tienes '.self::CONFIRMATION_MINUTES.' minutos).');
    }

    private function emailVerified(SocialUser $socialUser): bool
    {
        $raw = method_exists($socialUser, 'getRaw') ? $socialUser->getRaw() : [];

        return filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function failed(Request $request, ?string $intent, string $message): RedirectResponse
    {
        if ($intent === 'confirm' && $request->user()) {
            return redirect()->route('profile.edit', ['section' => 'security'])->with('error', $message);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
