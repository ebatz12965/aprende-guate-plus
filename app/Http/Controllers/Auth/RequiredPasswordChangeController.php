<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Cambio obligatorio de la contraseña temporal en el primer inicio de sesión. */
class RequiredPasswordChangeController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->password_change_required) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'La contraseña temporal no es correcta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la temporal.',
        ]);

        $password = $request->string('password')->toString();
        $user->forceFill(['password' => Hash::make($password), 'password_change_required' => false, 'remember_token' => Str::random(60)])->save();
        // Las demás sesiones abiertas con la contraseña temporal se cierran (middleware auth.session); esta sigue abierta.
        Auth::logoutOtherDevices($password);
        $request->session()->regenerate();
        Audit::log('accounts', 'Cambió su contraseña temporal', $user);

        return redirect()->route('dashboard')->with('success', 'Listo, tu contraseña nueva quedó guardada.');
    }
}
