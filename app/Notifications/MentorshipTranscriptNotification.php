<?php

namespace App\Notifications;

use App\Models\MentorshipMessage;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Services\MailConfigurationService;
use App\Support\EmailTemplates;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Conversación completa de una sesión de mentoría finalizada, para cada participante. */
class MentorshipTranscriptNotification extends Notification
{
    public function __construct(private readonly MentorshipSession $session) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && $notifiable->isSuspended() ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        app(MailConfigurationService::class)->apply();
        $session = $this->session->loadMissing(['student', 'mentor', 'completedBy', 'messages.sender']);
        $other = $notifiable->id === $session->mentor_id ? $session->student : $session->mentor;

        $transcript = $session->messages->map(fn (MentorshipMessage $message) => [
            'sender' => $message->is_automatic ? 'Aprende Guate' : ($message->sender->name ?? 'Participante'),
            'time' => $message->created_at->format('d/m/Y H:i'),
            'body' => $message->body,
            'automatic' => (bool) $message->is_automatic,
        ])->values()->all();

        return EmailTemplates::mailMessage('mentorship_transcript', [
            'nombre' => $notifiable->name,
            'otra_persona' => $other->name,
            'tema' => $session->topic,
            'fecha' => ($session->completed_at ?? now())->locale('es')->translatedFormat('j \d\e F \d\e Y, g:i a'),
            'finalizada_por' => $session->completed_by ? $session->completedBy->name : 'la plataforma',
            'cantidad_mensajes' => count($transcript),
        ], route('mentorship.sessions.show', $session), $transcript);
    }
}
