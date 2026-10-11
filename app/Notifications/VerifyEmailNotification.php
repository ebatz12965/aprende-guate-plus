<?php

namespace App\Notifications;

use App\Services\MailConfigurationService;
use App\Support\EmailTemplates;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        app(MailConfigurationService::class)->apply();

        return EmailTemplates::mailMessage('email_verification', [
            'nombre' => $notifiable->name,
            'correo' => $notifiable->getEmailForVerification(),
            'minutos' => config('auth.verification.expire', 60),
        ], $this->verificationUrl($notifiable));
    }
}
