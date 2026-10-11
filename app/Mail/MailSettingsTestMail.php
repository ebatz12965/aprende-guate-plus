<?php

namespace App\Mail;

use App\Support\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Correo de prueba de la configuración de correo saliente; su texto se edita en «Plantillas de correo». */
class MailSettingsTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $sentBy, public string $host) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->email()['subject']);
    }

    public function content(): Content
    {
        $email = $this->email();

        return new Content(markdown: $email['view'], with: $email['data']);
    }

    private function email(): array
    {
        return EmailTemplates::compose(EmailTemplates::fields('mail_test'), [
            'servidor' => $this->host,
            'enviado_por' => $this->sentBy,
            'fecha' => now()->locale('es')->translatedFormat('j \d\e F \d\e Y, g:i a'),
        ], config('app.url'));
    }
}
