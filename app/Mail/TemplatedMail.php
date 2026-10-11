<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Correo ya armado con App\Support\EmailTemplates::compose(); se usa en la vista previa y en los envíos de prueba. */
class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array{subject: string, view: string, data: array<string, mixed>}  $email */
    public function __construct(public array $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->email['subject']);
    }

    public function content(): Content
    {
        return new Content(markdown: $this->email['view'], with: $this->email['data']);
    }
}
