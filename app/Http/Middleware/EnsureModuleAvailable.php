<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bloquea las rutas de un módulo en mantenimiento u oculto en vista previa (el módulo sale del nombre de la ruta). */
class EnsureModuleAvailable
{
    public function __construct(private readonly Modules $modules) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->modules->forRoute($request->route()?->getName());
        if ($key === null) {
            return $next($request);
        }

        $access = $this->modules->access($request->user(), $key);
        abort_if($access === 'hidden', 404);

        if ($access === 'maintenance') {
            $message = $this->modules->maintenanceMessage($key);
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 503);
            }
            if (! $request->isMethod('GET')) {
                return back()->with('error', $message);
            }

            return response()->view('modules.unavailable', ['module' => Modules::CATALOG[$key], 'message' => $message], 503);
        }

        // El super administrador entra a un módulo cerrado para revisarlo; se le recuerda que los demás no lo ven.
        $status = $this->modules->state($key)['status'];
        if ($status !== Modules::ACTIVE) {
            view()->share('moduleNotice', $status === Modules::MAINTENANCE
                ? 'Este módulo está en mantenimiento: lo ves porque eres super administrador. Las demás personas ven el aviso de mantenimiento.'
                : 'Este módulo está en vista previa: solo lo ven los roles elegidos y el super administrador.');
        }

        return $next($request);
    }
}
