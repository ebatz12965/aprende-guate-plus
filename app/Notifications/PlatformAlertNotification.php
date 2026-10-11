<?php

namespace App\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\MailConfigurationService;
use App\Support\EmailTemplates;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformAlertNotification extends Notification
{
    public function __construct(
        private readonly string $category,
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $url = null,
        private readonly ?string $actionLabel = null,
        private readonly bool $allowEmail = true,
        private readonly bool $allowDatabase = true,
    ) {}

    public function via(object $notifiable): array
    {
        // Una cuenta suspendida no recibe avisos ni correos mientras dure la suspensión.
        if ($notifiable instanceof User && $notifiable->isSuspended()) {
            return [];
        }

        $preferences = NotificationPreference::forUser($notifiable);
        $channels = [];

        if ($this->allowDatabase && $preferences->receives('database', $this->category)) {
            $channels[] = 'database';
        }

        if ($this->allowEmail && $preferences->receives('mail', $this->category)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'action_label' => $this->actionLabel,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        app(MailConfigurationService::class)->apply();

        return EmailTemplates::mailMessage('platform_alert', [
            'nombre' => $notifiable->name,
            'titulo' => $this->title,
            'mensaje' => $this->body,
            'accion' => $this->actionLabel ?: 'Ver detalle',
        ], $this->url);
    }
}
