<?php

namespace App\Console\Commands;

use App\AI\AgentCatalog;
use App\AI\ProviderRegistry;
use App\Jobs\EvaluateChallengeSubmissionJob;
use App\Jobs\EvaluatePracticeAttemptJob;
use App\Models\ChallengeSubmission;
use App\Models\LearningPathPracticeAttempt;
use Illuminate\Console\Command;

/**
 * Reencola evidencias de retos y prácticas que quedaron sin evaluación automática.
 * requires_ai: el evaluador no estaba disponible. evaluation_failed: la evaluación lanzó un error
 * (solo con --include-failed, para no reintentar indefinidamente una entrega que siempre falla).
 */
class RetryAiEvaluations extends Command
{
    protected $signature = 'ai:retry-evaluations
        {--include-failed : Incluir también las evaluaciones que terminaron con error}
        {--limit=50 : Máximo de entregas por tipo en esta ejecución}';

    protected $description = 'Vuelve a encolar evidencias y prácticas pendientes de evaluación por IA';

    public function handle(AgentCatalog $catalog): int
    {
        if (! $this->evaluatorAvailable($catalog)) {
            $this->info('El evaluador IA no está disponible (agente desactivado o sin credencial); no se reencola nada.');

            return self::SUCCESS;
        }

        $statuses = $this->option('include-failed') ? ['requires_ai', 'evaluation_failed'] : ['requires_ai'];
        $limit = max(1, (int) $this->option('limit'));

        $submissions = ChallengeSubmission::whereIn('status', $statuses)->oldest('updated_at')->limit($limit)->get();
        foreach ($submissions as $submission) {
            $submission->update(['status' => 'queued']);
            EvaluateChallengeSubmissionJob::dispatch($submission->id)->onQueue('ai');
        }

        $attempts = LearningPathPracticeAttempt::whereIn('status', $statuses)->oldest('updated_at')->limit($limit)->get();
        foreach ($attempts as $attempt) {
            $attempt->update(['status' => 'queued']);
            EvaluatePracticeAttemptJob::dispatch($attempt->id)->onQueue('ai');
        }

        $this->info("Reencoladas: {$submissions->count()} evidencias de retos y {$attempts->count()} prácticas.");

        return self::SUCCESS;
    }

    private function evaluatorAvailable(AgentCatalog $catalog): bool
    {
        $agent = $catalog->configuration('evaluator');
        $provider = $agent['provider'] ?? config('ai.default_provider');

        return ($agent['enabled'] ?? false) && app(ProviderRegistry::class)->configured($provider);
    }
}
