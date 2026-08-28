<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect(string $provider)
    {
        abort_unless($provider === 'google', 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless($provider === 'google', 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
            $user = User::where('email', $socialUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => \Hash::make(\Str::random(24)),
                ]);
            }

            if ($user->getRoleNames()->isEmpty()) {
                $user->assignDefaultStudentRole();
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        } catch (\Exception $exception) {
            report($exception);

            return redirect()->route('login')->with('error', 'No fue posible iniciar sesión con Google. Inténtalo nuevamente.');
        }
    }
}
