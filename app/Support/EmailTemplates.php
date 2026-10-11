<?php

namespace App\Support;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Plantillas de los correos que envía la plataforma.
 *
 * El texto original vive aquí; el administrador lo personaliza desde el panel y la versión
 * editada se guarda en email_templates. Los datos variables se escriben como {marcador}.
 */
class EmailTemplates
{
    public const SIGNATURE = "Saludos,\nEl equipo de Aprende Guate";

    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        return [
            'password_reset' => [
                'name' => 'Restablecer contraseña',
                'description' => 'Se envía cuando alguien pide recuperar el acceso desde «¿Olvidaste tu contraseña?».',
                'subject' => 'Restablece tu contraseña · Aprende Guate',
                'heading' => 'Hola, {nombre}',
                'body' => 'Recibimos una solicitud para cambiar la contraseña de tu cuenta.',
                'action_label' => 'Restablecer contraseña',
                'closing' => "Este enlace expirará en {minutos} minutos. Si no solicitaste el cambio, no es necesario realizar ninguna acción.\n\n".self::SIGNATURE,
                'placeholders' => ['nombre' => 'Nombre de la persona', 'correo' => 'Correo de la cuenta', 'minutos' => 'Minutos de validez del enlace'],
                'sample' => ['nombre' => 'María López', 'correo' => 'maria@example.com', 'minutos' => '60'],
            ],
            'email_verification' => [
                'name' => 'Confirmar correo electrónico',
                'description' => 'Se envía al crear una cuenta para confirmar que el correo le pertenece a la persona.',
                'subject' => 'Confirma tu correo electrónico · Aprende Guate',
                'heading' => '¡Te damos la bienvenida a Aprende Guate!',
                'body' => 'Hola, {nombre}. Para activar tu cuenta, confirma que este correo te pertenece.',
                'action_label' => 'Confirmar mi correo',
                'closing' => "El enlace expirará en {minutos} minutos. Si no creaste esta cuenta, puedes ignorar este mensaje.\n\n".self::SIGNATURE,
                'placeholders' => ['nombre' => 'Nombre de la persona', 'correo' => 'Correo por confirmar', 'minutos' => 'Minutos de validez del enlace'],
                'sample' => ['nombre' => 'María López', 'correo' => 'maria@example.com', 'minutos' => '60'],
            ],
            'platform_alert' => [
                'name' => 'Avisos de la plataforma',
                'description' => 'Base de los avisos por correo: solicitudes de mentoría, recordatorios, evaluaciones, avisos del equipo, etc. El título y el mensaje los pone cada aviso.',
                'subject' => '{titulo} · Aprende Guate',
                'heading' => 'Hola, {nombre}',
                'body' => '{mensaje}',
                'action_label' => '{accion}',
                'closing' => "Puedes cambiar tus preferencias de notificaciones desde tu cuenta.\n\n".self::SIGNATURE,
                'placeholders' => ['nombre' => 'Nombre de la persona', 'titulo' => 'Título del aviso', 'mensaje' => 'Texto del aviso', 'accion' => 'Texto del botón que define cada aviso'],
                'sample' => ['nombre' => 'María López', 'titulo' => 'Recordatorio de mentoría', 'mensaje' => 'Recordatorio: tu sesión de mentoría sobre “Excel para análisis de ventas” es en una hora (11/10/2026 15:00, 30 minutos).', 'accion' => 'Abrir sesión'],
            ],
            'mentorship_transcript' => [
                'name' => 'Conversación de mentoría finalizada',
                'description' => 'Se envía a ambas personas al finalizar una sesión de mentoría, con la conversación completa debajo del mensaje.',
                'subject' => 'Conversación de tu mentoría: {tema} · Aprende Guate',
                'heading' => 'Hola, {nombre}',
                'body' => "Tu sesión de mentoría sobre «{tema}» con {otra_persona} finalizó el {fecha}.\n\nAquí tienes la conversación completa ({cantidad_mensajes} mensajes) para que la consultes cuando quieras.",
                'action_label' => 'Ver la sesión',
                'closing' => "Gracias por aprender en comunidad.\n\n".self::SIGNATURE,
                'placeholders' => ['nombre' => 'Nombre de quien recibe', 'otra_persona' => 'Nombre de la otra persona de la sesión', 'tema' => 'Tema de la mentoría', 'fecha' => 'Fecha y hora en que finalizó', 'finalizada_por' => 'Quién la finalizó (o «la plataforma»)', 'cantidad_mensajes' => 'Número de mensajes'],
                'sample' => ['nombre' => 'María López', 'otra_persona' => 'Carlos Pérez', 'tema' => 'Excel para análisis de ventas', 'fecha' => '11 de octubre de 2026, 3:45 p. m.', 'finalizada_por' => 'Carlos Pérez', 'cantidad_mensajes' => '3'],
                'sample_transcript' => [
                    ['sender' => 'Carlos Pérez', 'time' => '11/10/2026 15:02', 'body' => '¡Hola, María! Cuéntame qué parte de las tablas dinámicas te cuesta más.', 'automatic' => false],
                    ['sender' => 'María López', 'time' => '11/10/2026 15:05', 'body' => "Agrupar las ventas por mes.\nMe salen las fechas sueltas.", 'automatic' => false],
                    ['sender' => 'Carlos Pérez', 'time' => '11/10/2026 15:07', 'body' => 'Clic derecho sobre una fecha → Agrupar → Meses. Pruébalo y me cuentas.', 'automatic' => false],
                ],
            ],
            'mail_test' => [
                'name' => 'Prueba del correo saliente',
                'description' => 'Se envía desde «Correo saliente → Probar configuración» para comprobar que el servidor SMTP funciona.',
                'subject' => 'Prueba de correo · Aprende Guate',
                'heading' => '¡El correo saliente funciona!',
                'body' => "Si estás leyendo esto, Aprende Guate ya puede enviar correos: verificación de cuentas, restablecimiento de contraseñas y avisos de la plataforma.\n\n**Servidor de correo:** {servidor}\n**Enviado por:** {enviado_por}\n**Fecha:** {fecha}\n\nSi este correo llegó a la carpeta de spam, revisa los registros SPF y DKIM de tu dominio.",
                'action_label' => 'Ir a Aprende Guate',
                'closing' => self::SIGNATURE,
                'placeholders' => ['servidor' => 'Servidor SMTP usado', 'enviado_por' => 'Administrador que envió la prueba', 'fecha' => 'Fecha y hora del envío'],
                'sample' => ['servidor' => 'smtp.ejemplo.com', 'enviado_por' => 'Administración', 'fecha' => '11 de octubre de 2026, 3:45 p. m.'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function definition(string $key): array
    {
        return self::definitions()[$key] ?? throw new InvalidArgumentException("Plantilla de correo desconocida: {$key}");
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    /** Campos vigentes: los personalizados si existen, si no los originales. */
    public static function fields(string $key): array
    {
        $definition = self::definition($key);
        $custom = EmailTemplate::query()->where('key', $key)->first();

        $fields = [];
        foreach (['subject', 'heading', 'body', 'action_label', 'closing'] as $field) {
            $fields[$field] = $custom ? (string) $custom->{$field} : (string) $definition[$field];
        }

        return $fields;
    }

    /** Marcadores {así} que aparecen en un texto. */
    public static function placeholdersIn(string $text): Collection
    {
        preg_match_all('/\{([a-z_]+)\}/u', $text, $matches);

        return collect($matches[1])->unique()->values();
    }

    /**
     * Arma el correo con los valores de este envío.
     *
     * @param  array<string, string>  $fields  subject, heading, body, action_label, closing
     * @param  array<string, scalar|null>  $values
     * @param  list<array{sender: string, time: string, body: string, automatic: bool}>|null  $transcript
     * @return array{subject: string, view: string, data: array<string, mixed>}
     */
    public static function compose(array $fields, array $values, ?string $actionUrl = null, ?array $transcript = null): array
    {
        $values = array_map(fn ($value) => (string) $value, $values);
        $plain = fn (string $text) => self::fill($text, $values, false);
        $markdown = fn (string $text) => self::fill($text, $values, true);

        $actionLabel = trim($plain($fields['action_label'] ?? ''));

        return [
            'subject' => trim(preg_replace('/\s+/u', ' ', $plain($fields['subject']))),
            'view' => 'mail.template',
            'data' => [
                'heading' => trim($markdown($fields['heading'] ?? '')),
                'paragraphs' => self::paragraphs($markdown($fields['body'] ?? '')),
                'transcriptHtml' => $transcript === null ? null : self::transcriptHtml($transcript),
                'actionUrl' => $actionUrl,
                'actionLabel' => $actionUrl ? ($actionLabel !== '' ? $actionLabel : 'Ver detalle') : null,
                'closing' => self::paragraphs($markdown($fields['closing'] ?? '')),
            ],
        ];
    }

    /** Correo listo para una notificación, con la plantilla vigente. */
    public static function mailMessage(string $key, array $values, ?string $actionUrl = null, ?array $transcript = null): MailMessage
    {
        $email = self::compose(self::fields($key), $values, $actionUrl, $transcript);

        return (new MailMessage)->subject($email['subject'])->markdown($email['view'], $email['data']);
    }

    /** Valores de ejemplo para la vista previa y los envíos de prueba. */
    public static function sampleValues(string $key): array
    {
        return self::definition($key)['sample'];
    }

    public static function sampleTranscript(string $key): ?array
    {
        return self::definition($key)['sample_transcript'] ?? null;
    }

    private static function fill(string $text, array $values, bool $escapeMarkdown): string
    {
        return preg_replace_callback('/\{([a-z_]+)\}/u', function (array $match) use ($values, $escapeMarkdown) {
            if (! array_key_exists($match[1], $values)) {
                return $match[0];
            }

            // Lo que escribe una persona (nombres, mensajes) nunca se interpreta como formato ni como enlace.
            return $escapeMarkdown ? self::escapeMarkdown($values[$match[1]]) : $values[$match[1]];
        }, $text);
    }

    private static function escapeMarkdown(string $value): string
    {
        return preg_replace('/([\\\\`*_\[\]#!|~])/u', '\\\\$1', $value);
    }

    /** Párrafos separados por una línea en blanco; dentro de un párrafo se respetan los saltos de línea. */
    private static function paragraphs(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return collect(preg_split('/\n\s*\n/u', $text))
            ->map(fn (string $paragraph) => collect(explode("\n", trim($paragraph)))->map(fn (string $line) => e(trim($line)))->implode('<br>'))
            ->filter(fn (string $paragraph) => $paragraph !== '')
            ->values()
            ->all();
    }

    /** La conversación va en una tabla HTML en una sola línea para que el Markdown del correo no la toque. */
    private static function transcriptHtml(array $transcript): string
    {
        if ($transcript === []) {
            return '<table class="transcript" width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td class="transcript-empty">La sesión terminó sin mensajes.</td></tr></table>';
        }

        $rows = collect($transcript)->map(function (array $message) {
            $meta = '<strong>'.e($message['sender']).'</strong> · '.e($message['time']).($message['automatic'] ? ' · Mensaje automático' : '');
            $body = collect(preg_split('/\r\n|\r|\n/', $message['body']))->map(fn (string $line) => e($line))->implode('<br>');

            return '<tr><td class="transcript-row"><p class="transcript-meta">'.$meta.'</p><p class="transcript-body">'.$body.'</p></td></tr>';
        })->implode('');

        return '<table class="transcript" width="100%" cellpadding="0" cellspacing="0" role="presentation">'.$rows.'</table>';
    }
}
