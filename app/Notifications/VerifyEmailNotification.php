<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirma tu correo electrónico · Aprende Guate')
            ->greeting('¡Bienvenido a Aprende Guate!')
            ->line('Para activar tu cuenta, confirma que este correo te pertenece.')
            ->action('Confirmar mi correo', $verificationUrl)
            ->line('El enlace expirará en 60 minutos. Si no creaste esta cuenta, puedes ignorar este mensaje.');
    }
}
