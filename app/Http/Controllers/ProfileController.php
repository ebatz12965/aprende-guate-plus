<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\NotificationPreference;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request, TwoFactorAuthenticationService $twoFactor): View
    {
        $setupSecret = $request->session()->get('two_factor.setup_secret');

        return view('profile.edit', [
            'user' => $request->user(),
            'notificationPreferences' => NotificationPreference::forUser($request->user()),
            'twoFactorSetupSecret' => $setupSecret,
            'twoFactorQrCode' => $setupSecret ? $twoFactor->qrCodeDataUri($request->user(), $setupSecret) : null,
            'openTwoFactorSetup' => $request->boolean('setup') && (bool) $setupSecret,
            'twoFactorRecoveryCodes' => $request->session()->pull('two_factor.recovery_codes'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', ['password' => ['required', 'current_password']]);
        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
