<?php

namespace App\Support;

use App\Models\PlatformModule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Módulos de la plataforma que el super administrador puede poner en mantenimiento o en vista previa.
 *
 * - Activo: lo usa todo el mundo.
 * - En mantenimiento: aparece en el menú con una etiqueta y, al entrar, muestra el aviso; nadie lo usa salvo el
 *   super administrador (para verificar el arreglo). Si tiene fecha de regreso, se reactiva solo al llegar.
 * - Vista previa: solo lo ven los roles elegidos y el super administrador; para los demás no existe (404).
 *
 * El módulo de cada petición se reconoce por el nombre de su ruta (EnsureModuleAvailable), así que una ruta nueva
 * queda protegida en cuanto su nombre coincide con un patrón del catálogo. Lo que ya estaba en la cola termina.
 */
class Modules
{
    public const ACTIVE = 'active';

    public const MAINTENANCE = 'maintenance';

    public const PREVIEW = 'preview';

    public const STATUSES = [self::ACTIVE => 'Activo', self::MAINTENANCE => 'En mantenimiento', self::PREVIEW => 'Vista previa'];

    /** @var array<string, array{label: string, description: string, routes: list<string>, default: string, depends?: list<string>, note?: string}> */
    public const CATALOG = [
        'diagnostico' => [
            'label' => 'Diagnóstico',
            'description' => 'Meta profesional, cuestionario de nivel y generación de la ruta.',
            'routes' => ['adaptive.diagnostic', 'adaptive.diagnostic.*'],
            'default' => self::ACTIVE,
        ],
        'rutas' => [
            'label' => 'Rutas de aprendizaje',
            'description' => 'Fases, temas, lecciones y prácticas.',
            'routes' => ['adaptive.path', 'adaptive.path.*', 'adaptive.topic.*'],
            'default' => self::ACTIVE,
        ],
        'tutor' => [
            'label' => 'Tutor IA',
            'description' => 'Conversación con el tutor.',
            'routes' => ['adaptive.tutor', 'adaptive.tutor.*'],
            'default' => self::ACTIVE,
        ],
        'retos' => [
            'label' => 'Retos',
            'description' => 'Retos de hito, entregas y su evaluación.',
            'routes' => ['adaptive.challenges', 'adaptive.challenges.*'],
            'default' => self::ACTIVE,
            'depends' => ['rutas'],
        ],
        'mentoria' => [
            'label' => 'Mentoría',
            'description' => 'Solicitudes, sesiones y chat, también desde el espacio de mentor.',
            'routes' => ['adaptive.mentorship', 'adaptive.mentorship.*', 'mentorship.*', 'mentor.*'],
            'default' => self::ACTIVE,
            'note' => 'Los recordatorios de las sesiones ya programadas se siguen enviando: son citas acordadas entre personas.',
        ],
        'postulacion_mentor' => [
            'label' => 'Solicitud para ser mentor',
            'description' => 'Cuestionario y envío de la postulación.',
            'routes' => ['mentor-application.*'],
            'default' => self::ACTIVE,
        ],
        'perfil' => [
            'label' => 'Perfil profesional',
            'description' => 'Edición del perfil y del CV, y el enlace público para empleadores.',
            'routes' => ['adaptive.career', 'adaptive.career.*', 'adaptive.public-profile'],
            'default' => self::ACTIVE,
        ],
        'entrevistas' => [
            'label' => 'Entrevistas',
            'description' => 'Simulador de postulación: entrevistas y pruebas con IA.',
            'routes' => ['adaptive.interviews', 'adaptive.interviews.*'],
            'default' => self::PREVIEW,
        ],
    ];

    private const CACHE_KEY = 'platform_modules.state';

    /** @var array<string, array<string, mixed>>|null */
    private ?array $rows = null;

    /**
     * Estado efectivo: un mantenimiento con fecha de regreso ya cumplida cuenta como activo.
     *
     * @return array{status: string, message: ?string, until: ?Carbon, roles: list<string>, expired: bool}
     */
    public function state(string $key): array
    {
        $row = $this->rows()[$key] ?? null;
        $status = $row['status'] ?? (self::CATALOG[$key]['default'] ?? self::ACTIVE);
        $until = isset($row['until']) ? Carbon::parse($row['until']) : null;
        $expired = $status === self::MAINTENANCE && $until !== null && $until->isPast();

        return [
            'status' => $expired ? self::ACTIVE : $status,
            'message' => $row['message'] ?? null,
            'until' => $until,
            'roles' => array_values($row['preview_roles'] ?? []),
            'expired' => $expired,
        ];
    }

    /** 'allowed', 'maintenance' (lo ve con aviso) o 'hidden' (vista previa sin permiso). */
    public function access(?User $user, string $key): string
    {
        $state = $this->state($key);

        return match ($state['status']) {
            self::MAINTENANCE => $user?->isSuperAdmin() ? 'allowed' : 'maintenance',
            self::PREVIEW => $user && ($user->isSuperAdmin() || ($state['roles'] && $user->hasAnyRole($state['roles']))) ? 'allowed' : 'hidden',
            default => 'allowed',
        };
    }

    public function visibleTo(?User $user, string $key): bool
    {
        return $this->access($user, $key) !== 'hidden';
    }

    public function underMaintenance(string $key): bool
    {
        return $this->state($key)['status'] === self::MAINTENANCE;
    }

    public function forRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }
        foreach (self::CATALOG as $key => $module) {
            if (Str::is($module['routes'], $routeName)) {
                return $key;
            }
        }

        return null;
    }

    /** Mensaje para la persona que intenta usar un módulo en mantenimiento. */
    public function maintenanceMessage(string $key): string
    {
        $state = $this->state($key);
        $text = $state['message'] ?: self::CATALOG[$key]['label'].' está en mantenimiento por unos momentos.';
        if ($state['until']) {
            $text .= ' Volverá a estar disponible el '.$state['until']->format('d/m/Y').' a las '.$state['until']->format('H:i').'.';
        }

        return $text;
    }

    /** @param  list<string>  $previewRoles */
    public function update(User $actor, string $key, string $status, ?string $message, ?Carbon $until, array $previewRoles): void
    {
        $before = $this->state($key);
        $label = self::CATALOG[$key]['label'];
        PlatformModule::updateOrCreate(['key' => $key], [
            'status' => $status,
            'message' => $status === self::MAINTENANCE ? $message : null,
            'until' => $status === self::MAINTENANCE ? $until : null,
            'preview_roles' => $status === self::PREVIEW ? $previewRoles : null,
            'updated_by' => $actor->id,
        ]);
        $this->flush();

        $description = match ($status) {
            self::MAINTENANCE => "Puso en mantenimiento el módulo «{$label}»",
            self::PREVIEW => "Puso en vista previa el módulo «{$label}»",
            default => "Activó el módulo «{$label}»",
        };
        Audit::log('configuracion', $description, null, array_filter([
            'antes' => self::STATUSES[$before['status']],
            'mensaje' => $status === self::MAINTENANCE ? $message : null,
            'hasta' => $status === self::MAINTENANCE ? $until?->format('d/m/Y H:i') : null,
            'roles con acceso' => $status === self::PREVIEW ? (array_map(AdminAccess::roleLabel(...), $previewRoles) ?: ['Solo super administrador']) : null,
        ]));
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->rows = null;
    }

    /** @return array<string, array<string, mixed>> */
    private function rows(): array
    {
        return $this->rows ??= Cache::remember(self::CACHE_KEY, 300, fn () => PlatformModule::all()
            ->mapWithKeys(fn (PlatformModule $module) => [$module->key => [
                'status' => $module->status,
                'message' => $module->message,
                'until' => $module->until?->toIso8601String(),
                'preview_roles' => $module->preview_roles,
            ]])->all());
    }
}
