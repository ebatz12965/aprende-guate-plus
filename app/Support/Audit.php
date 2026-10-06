<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora de acciones administrativas (spatie/laravel-activitylog).
 * Nunca se registran contraseñas, claves ni el contenido de los formularios de configuración: solo qué cambió.
 */
final class Audit
{
    public const LOGS = [
        'usuarios' => 'Usuarios',
        'roles' => 'Roles',
        'mentores' => 'Mentores',
        'avisos' => 'Avisos',
        'configuracion' => 'Configuración',
        'acceso' => 'Acceso',
    ];

    /** @param  array<string, mixed>  $properties */
    public static function log(string $log, string $description, ?Model $subject = null, array $properties = [], ?Model $causer = null): void
    {
        $entry = activity($log)->withProperties($properties);
        $causer ??= auth()->user();
        if ($causer) {
            $entry->causedBy($causer);
        }
        if ($subject) {
            $entry->performedOn($subject);
        }
        $entry->log($description);
    }
}
