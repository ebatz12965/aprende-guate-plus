<?php

namespace App\Notifications;

use App\Services\MailConfigurationService;
use App\Support\EmailTemplates;
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

        return EmailTemplates::mailMessage('password_reset', [
            'nombre' => $notifiable->name,
            'correo' => $notifiable->getEmailForPasswordReset(),
            'minutos' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
        ], $url);
    }
}
