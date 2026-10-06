<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforceIdleTimeout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Actividad real desde el navegador (lectura sin peticiones) y cierre por inactividad avisado por el navegador. */
class SessionActivityController extends Controller
{
    /** El middleware ya registró la actividad: esta petición no lleva el encabezado de fondo. */
    public function ping(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    public function expire(Request $request): RedirectResponse
    {
        $minutes = EnforceIdleTimeout::limitFor($request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', "Cerramos tu sesión porque pasaron más de {$minutes} minutos sin actividad.");
    }
}
