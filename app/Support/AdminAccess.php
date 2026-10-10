<?php

namespace App\Support;

/**
 * Catálogo de permisos de administración.
 *
 * - El rol Admin es el super administrador: tiene todos estos permisos sin asignarlos uno a uno.
 * - Los permisos delegables se guardan en la tabla permissions y se agrupan en roles personalizados
 *   (por ejemplo "Soporte") que el super administrador crea desde el panel.
 * - Los permisos exclusivos nunca se delegan: manejan credenciales, gasto de IA o el propio control de acceso.
 *
 * Solo cubre habilidades de administración: las policies de los datos de cada estudiante no se saltan.
 */
final class AdminAccess
{
    public const SUPER_ROLE = 'Admin';

    public const MENTOR_ROLE = 'Instructor';

    public const BASE_ROLE = 'Student';

    public const SYSTEM_ROLES = [self::SUPER_ROLE, self::MENTOR_ROLE, self::BASE_ROLE];

    /** Rol personalizado que se crea de fábrica: un nivel debajo del super administrador, con permisos editables. */
    public const ADMIN_ROLE = 'Administrador';

    /** @var array<string, array{0: string, 1: string}> permiso => [grupo, descripción] */
    public const DELEGABLE = [
        'usuarios.ver' => ['Usuarios', 'Ver la lista de usuarios y el historial de cada cuenta'],
        'usuarios.gestionar' => ['Usuarios', 'Crear usuarios y editar su nombre, correo y contraseña'],
        'usuarios.suspender' => ['Usuarios', 'Suspender y reactivar cuentas'],
        'roles.asignar' => ['Usuarios', 'Asignar roles a usuarios (solo roles cuyos permisos también tenga)'],
        'mentores.revisar' => ['Mentoría', 'Aprobar o rechazar solicitudes para ser mentor'],
        'avisos.publicar' => ['Comunicación', 'Crear, publicar y eliminar avisos'],
        'ia.tablero' => ['Inteligencia artificial', 'Ver el tablero de IA en vivo'],
        'ia.consumo' => ['Inteligencia artificial', 'Ver el consumo de IA por persona'],
        'bitacora.ver' => ['Auditoría', 'Consultar la bitácora de actividad administrativa'],
    ];

    /** @var array<string, string> permiso => descripción */
    public const SUPER_ONLY = [
        'usuarios.eliminar' => 'Eliminar cuentas definitivamente',
        'roles.gestionar' => 'Crear, editar y eliminar roles y sus permisos',
        'ia.configuracion' => 'Límites de uso y presupuesto de tokens de IA',
        'ia.orquestador' => 'Orquestador de agentes y precios de modelos',
        'correo.configurar' => 'Correo saliente (SMTP)',
        'modulos.gestionar' => 'Poner módulos en mantenimiento o en vista previa',
    ];

    /** @return list<string> */
    public static function delegable(): array
    {
        return array_keys(self::DELEGABLE);
    }

    public static function isAdminAbility(string $ability): bool
    {
        return isset(self::DELEGABLE[$ability]) || isset(self::SUPER_ONLY[$ability]);
    }

    public static function isSystemRole(string $role): bool
    {
        return in_array($role, self::SYSTEM_ROLES, true);
    }

    /** Orden de presentación: super administrador, administrador, mentor, estudiante y luego los personalizados. */
    public static function sortKey(string $role): string
    {
        $order = [self::SUPER_ROLE, self::ADMIN_ROLE, self::MENTOR_ROLE, self::BASE_ROLE];
        $position = array_search($role, $order, true);

        return ($position === false ? '9' : (string) $position).mb_strtolower($role);
    }

    /** Nombre visible de un rol: los del sistema conservan su nombre técnico en la base. */
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            self::SUPER_ROLE => 'Super administrador',
            self::MENTOR_ROLE => 'Mentor',
            self::BASE_ROLE => 'Estudiante',
            default => $role,
        };
    }

    public static function roleDescription(string $role): ?string
    {
        return match ($role) {
            self::SUPER_ROLE => 'Todos los permisos de administración, incluidos los exclusivos.',
            self::MENTOR_ROLE => 'Espacio de mentor: disponibilidad, solicitudes, agenda y chat.',
            self::BASE_ROLE => 'Espacio de aprendizaje. Todas las cuentas lo tienen.',
            default => null,
        };
    }

    /** @return array<string, array<string, string>> grupo => [permiso => descripción] */
    public static function delegableByGroup(): array
    {
        $groups = [];
        foreach (self::DELEGABLE as $permission => [$group, $description]) {
            $groups[$group][$permission] = $description;
        }

        return $groups;
    }
}
