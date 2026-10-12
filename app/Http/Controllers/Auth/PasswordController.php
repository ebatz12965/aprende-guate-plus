<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SocialiteController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Cuenta creada con Google: no hay contraseña actual que pedir; en su lugar debe haber confirmado su identidad con Google hace poco.
        if (! $user->has_local_password) {
            if (! SocialiteController::recentlyConfirmed($request)) {
                return back()->withErrors(['current_password' => 'Primero confirma tu identidad con Google para crear tu contraseña.'], 'updatePassword');
            }
            $rules = ['password' => ['required', Password::defaults(), 'confirmed']];
        } else {
            $rules = [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ];
        }

        $validated = $request->validateWithBag('updatePassword', $rules);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_change_required' => false,
            'has_local_password' => true,
        ])->save();
        $request->session()->forget('auth.google_confirmed_at');

        return back()->with('status', 'password-updated');
    }
}
