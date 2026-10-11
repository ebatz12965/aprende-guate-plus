<?php

namespace App\Services;

use App\Models\MentorshipSession;
use App\Models\User;
use App\Notifications\MentorshipTranscriptNotification;
use App\Notifications\PlatformAlertNotification;

/** Cierra una sesión de mentoría y envía la conversación completa a ambas personas. */
class MentorshipCompletionService
{
    /** Horas después del final programado en que la plataforma cierra sola la sesión. */
    public const AUTO_CLOSE_AFTER_HOURS = 24;

    /** Devuelve false si la sesión ya no estaba abierta (otra persona la cerró antes). */
    public function complete(MentorshipSession $session, ?User $by): bool
    {
        $updated = MentorshipSession::query()
            ->whereKey($session->id)
            ->whereIn('status', ['accepted', 'scheduled'])
            ->update(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $by?->id, 'updated_at' => now()]);

        if ($updated === 0) {
            return false;
        }

        $session->refresh()->load(['student', 'mentor']);

        foreach ([$session->student, $session->mentor] as $participant) {
            if ($participant === null || $participant->is($by)) {
                continue;
            }
            $participant->notify(new PlatformAlertNotification(
                category: 'mentorship',
                title: 'Sesión de mentoría finalizada',
                body: ($by ? $by->name.' finalizó' : 'Se cerró automáticamente').' la sesión sobre “'.$session->topic.'”. Te enviamos la conversación completa por correo.',
                url: route('mentorship.sessions.show', $session),
                actionLabel: 'Ver la conversación',
                allowEmail: false,
            ));
        }

        return true;
    }

    /** Envía la conversación a ambas personas una sola vez. */
    public function sendTranscripts(MentorshipSession $session): void
    {
        $claimed = MentorshipSession::query()
            ->whereKey($session->id)
            ->where('status', 'completed')
            ->whereNull('transcript_sent_at')
            ->update(['transcript_sent_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $session->refresh();
        foreach ([$session->student, $session->mentor] as $participant) {
            if ($participant === null) {
                continue;
            }
            try {
                $participant->notify(new MentorshipTranscriptNotification($session));
            } catch (\Throwable $exception) {
                // Si falla un envío, el otro participante igual recibe su copia; el error queda registrado.
                report($exception);
            }
        }
    }
}
