<?php

namespace App\Notifications;

use App\Services\MailConfigurationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        app(MailConfigurationService::class)->apply();
        $url = URL::route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Restablece tu contraseña · Aprende Guate')
            ->greeting('Solicitud de restablecimiento de contraseña')
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line('Este enlace expirará en 60 minutos. Si no solicitaste el cambio, no es necesario realizar ninguna acción.');
    }
}
