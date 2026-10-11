<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Correo de prueba de la configuración de correo saliente, con el diseño de Aprende Guate. */
class MailSettingsTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $sentBy, public string $host) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Prueba de correo · Aprende Guate');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.settings-test', with: ['sentAt' => now()->locale('es')->translatedFormat('j \d\e F \d\e Y, g:i a')]);
    }
}
