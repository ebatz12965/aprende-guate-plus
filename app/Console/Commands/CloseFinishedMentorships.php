<?php

namespace App\Console\Commands;

use App\Models\MentorshipSession;
use App\Services\MentorshipCompletionService;
use Illuminate\Console\Command;

class CloseFinishedMentorships extends Command
{
    protected $signature = 'mentorship:close-finished';

    protected $description = 'Cierra las sesiones de mentoría que nadie finalizó y envía las conversaciones pendientes';

    public function handle(MentorshipCompletionService $completion): int
    {
        $closed = 0;
        $limit = now()->subHours(MentorshipCompletionService::AUTO_CLOSE_AFTER_HOURS);

        MentorshipSession::query()
            ->whereIn('status', ['accepted', 'scheduled'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $limit)
            ->each(function (MentorshipSession $session) use ($completion, $limit, &$closed): void {
                // El límite cuenta desde el final de la sesión, no desde su inicio.
                if ($session->scheduled_at->greaterThan($limit->copy()->subMinutes((int) $session->duration_minutes))) {
                    return;
                }
                if ($completion->complete($session, null)) {
                    $completion->sendTranscripts($session);
                    $closed++;
                }
            });

        // Conversaciones que no se enviaron al finalizar (por ejemplo, si el proceso se interrumpió).
        $resent = 0;
        MentorshipSession::query()
            ->where('status', 'completed')
            ->whereNull('transcript_sent_at')
            ->where('completed_at', '<', now()->subMinutes(10))
            ->each(function (MentorshipSession $session) use ($completion, &$resent): void {
                $completion->sendTranscripts($session);
                $resent++;
            });

        $this->info("Sesiones cerradas: {$closed}. Conversaciones reenviadas: {$resent}.");

        return self::SUCCESS;
    }
}
