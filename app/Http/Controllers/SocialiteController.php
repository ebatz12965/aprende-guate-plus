<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SocialiteController extends Controller
{
    /**
     * Redirige al usuario a la página de autenticación del proveedor.
     *
     * @param string $provider
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Maneja el callback de autenticación del proveedor.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(string $provider)
    {
        try {
            // Obtener el usuario de Socialite
            $socialUser = Socialite::driver($provider)->user();

            // Buscar si el usuario ya existe en la base de datos por su ID de Google
            $user = User::where('email', $socialUser->getEmail())->first();

            // Si el usuario no existe, crearlo
            if (!$user) {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => \Hash::make(\Str::random(24)),
                ]);
            }

            if ($user->getRoleNames()->isEmpty()) {
                $user->assignRole('Student');
            }

            // Autenticar al usuario y redirigirlo al dashboard
            Auth::login($user);

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            // Manejar errores, como si el usuario cancela la autenticación
            return redirect()->route('login')->with('error', 'Authentication failed.');
        }
    }
}
