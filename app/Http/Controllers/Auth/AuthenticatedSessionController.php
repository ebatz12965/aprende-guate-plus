<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor.login_user_id', $user->id);
            $request->session()->put('two_factor.remember', $this->remember($request, $user));

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $this->remember($request, $user));
        $request->session()->regenerate();

        return $this->completeLogin($request, $user);
    }

    /** «Recordar mi sesión» no aplica a cuentas con permisos de administración: deben escribir su contraseña cada vez. */
    private function remember(Request $request, User $user): bool
    {
        return $request->boolean('remember') && ! $user->canAccessAdmin();
    }

    public function completeLogin(Request $request, User $user): RedirectResponse
    {
        if ($user->getRoleNames()->isEmpty()) {
            $user->assignDefaultStudentRole();
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        $redirect = redirect()->intended(route('dashboard', absolute: false));
        if (! $user->hasTwoFactorEnabled() && $user->two_factor_reminder_shown_at === null) {
            $user->forceFill(['two_factor_reminder_shown_at' => now()])->save();
            $redirect->with('two_factor_reminder', true);
        }

        return $redirect;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
