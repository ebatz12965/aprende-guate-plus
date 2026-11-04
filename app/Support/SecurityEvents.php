<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Registro y alertas de seguridad del acceso (OWASP A09).
 *
 * Cada intento fallido queda en la bitácora con el correo escrito y la IP; nunca se guarda la contraseña.
 * Un bloqueo por intentos fallidos se registra una vez por ventana y avisa a los súper administradores,
 * con un tope global para que un ataque con muchos correos no los inunde de avisos.
 */
final class SecurityEvents
{
    /** Minutos durante los que un mismo bloqueo no se vuelve a registrar ni a avisar. */
    public const LOCKOUT_WINDOW_MINUTES = 15;

    /** Avisos de bloqueo como máximo por hora, sumando todos los correos e IP. */
    public const MAX_ALERTS_PER_HOUR = 10;

    public static function failedLogin(string $email, ?string $ip): void
    {
        $email = mb_strtolower(trim($email));
        $user = User::query()->where('email', $email)->first();
        Audit::log('acceso', 'Inicio de sesión fallido', $user, ['correo' => $email, 'ip' => $ip]);
    }

    public static function failedTwoFactor(User $user, ?string $ip): void
    {
        Audit::log('acceso', 'Código de verificación en dos pasos incorrecto', $user, ['ip' => $ip]);
    }

    /** @param  'login'|'two_factor'  $kind */
    public static function lockout(string $kind, string $email, ?string $ip): void
    {
        $email = mb_strtolower(trim($email));
        if (! Cache::add('security-lockout:'.sha1($kind.'|'.$email.'|'.$ip), true, now()->addMinutes(self::LOCKOUT_WINDOW_MINUTES))) {
            return;
        }

        $what = $kind === 'login' ? 'inicio de sesión' : 'verificación en dos pasos';
        $user = User::query()->where('email', $email)->first();
        Audit::log('acceso', "Bloqueo temporal por intentos fallidos de {$what}", $user, ['correo' => $email, 'ip' => $ip]);

        $allowed = RateLimiter::attempt('security-lockout-alerts', self::MAX_ALERTS_PER_HOUR, fn () => true, 3600);
        if (! $allowed) {
            return;
        }

        $alert = new PlatformAlertNotification(
            category: 'platform',
            title: 'Alerta de seguridad: bloqueo por intentos fallidos',
            body: "Se bloqueó temporalmente el {$what} de la cuenta {$email} desde la IP ".($ip ?? 'desconocida')
                .' tras varios intentos fallidos. Si no reconoces la actividad, revisa la bitácora.',
            url: route('admin.audit.index', ['categoria' => 'acceso']),
            actionLabel: 'Ver bitácora',
        );
        User::role(AdminAccess::SUPER_ROLE)->get()->each(fn (User $admin) => $admin->notify($alert));
    }
}
