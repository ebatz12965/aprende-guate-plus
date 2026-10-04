<?php

namespace App\Console\Commands;

use App\Models\MentorshipSession;
use App\Notifications\PlatformAlertNotification;
use Illuminate\Console\Command;

class SendMentorshipReminders extends Command
{
    protected $signature = 'mentorship:send-reminders';
    protected $description = 'Envía recordatorios de sesiones de mentoría programadas';

    public function handle(): int
    {
        $now = now();
        $sent = 0;

        MentorshipSession::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$now, $now->copy()->addDay()->addMinutes(2)])
            ->with(['student', 'mentor'])
            ->each(function (MentorshipSession $session) use ($now, &$sent): void {
                $minutes = $now->diffInMinutes($session->scheduled_at, false);
                $offsets = [1440 => 'un día antes', 720 => 'esta mañana', 360 => 'este mediodía', 180 => 'esta tarde', 60 => 'una hora', 30 => '30 minutos', 10 => '10 minutos'];
                $offset = collect(array_keys($offsets))->first(fn (int $value) => abs($minutes - $value) <= 1);
                if ($offset === null) {
                    return;
                }

                $sentReminders = $session->reminders_sent ?? [];
                $key = (string) $offset;
                if (in_array($key, $sentReminders, true)) {
                    return;
                }

                $body = 'Recordatorio: tu sesión de mentoría sobre “'.$session->topic.'” es '.$offsets[$offset].' ('.$session->scheduled_at->format('d/m/Y H:i').', '.$session->duration_minutes.' minutos).';
                foreach ([$session->student, $session->mentor] as $recipient) {
                    $recipient->notify(new PlatformAlertNotification(
                        category: 'mentorship',
                        title: 'Recordatorio de mentoría',
                        body: $body,
                        url: route('mentorship.sessions.show', $session),
                        actionLabel: 'Abrir sesión',
                    ));
                }

                $session->update(['reminders_sent' => [...$sentReminders, $key]]);
                $sent += 2;
            });

        $this->info("Recordatorios enviados: {$sent}");
        return self::SUCCESS;
    }
}
