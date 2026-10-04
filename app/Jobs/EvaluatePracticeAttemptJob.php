<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\LearningPathPracticeAttempt;
use App\Models\SkillEvidence;
use App\Services\Learning\TopicContentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Evalúa una práctica entregada. La evaluación es formativa: el tema ya quedó completado al
 * entregar; aquí solo se agrega retroalimentación y se ajusta la habilidad con el puntaje real.
 */
class EvaluatePracticeAttemptJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $attemptId)
    {
        $this->timeout = AiGateway::jobTimeout(1);
    }

    public function handle(TopicContentService $content): void
    {
        $attempt = LearningPathPracticeAttempt::with('practice.topic')->find($this->attemptId);
        if (! $attempt || ! in_array($attempt->status, ['queued', 'requires_ai'], true)) {
            return;
        }

        $attempt->update(['status' => 'evaluating']);

        try {
            $evaluation = $content->evaluatePractice($attempt);
        } catch (Throwable $exception) {
            $this->failed($exception);

            throw $exception;
        }

        if (! $evaluation) {
            $attempt->update(['status' => 'requires_ai', 'feedback' => 'Tu práctica quedó registrada. La retroalimentación automática estará disponible cuando el evaluador IA esté activo.']);

            return;
        }

        $attempt->update([
            'status' => 'evaluated',
            'score' => $evaluation['score'],
            'feedback' => $evaluation['summary'],
            'evaluation' => $evaluation,
            'evaluated_at' => now(),
        ]);

        $this->updateSkill($attempt, $evaluation['score']);
    }

    public function failed(?Throwable $exception): void
    {
        LearningPathPracticeAttempt::whereKey($this->attemptId)->update([
            'status' => 'evaluation_failed',
            'feedback' => 'Tu práctica quedó registrada, pero no se pudo generar la retroalimentación.',
        ]);
    }

    /** Confianza = promedio de puntajes evaluados de la habilidad; nivel 1–3 según ese promedio. */
    private function updateSkill(LearningPathPracticeAttempt $attempt, int $score): void
    {
        $evidence = SkillEvidence::with('userSkill')
            ->where('source_type', 'learning_path_practice_attempt')
            ->where('source_id', $attempt->id)
            ->first();

        if (! $evidence?->userSkill) {
            return;
        }

        $evidence->update(['score' => $score]);
        $userSkill = $evidence->userSkill;
        $average = (float) SkillEvidence::where('user_skill_id', $userSkill->id)->whereNotNull('score')->avg('score');

        $userSkill->update([
            'confidence' => round($average, 2),
            'level' => match (true) {
                $average >= 85 => 3,
                $average >= 60 => 2,
                default => 1,
            },
            'last_evidenced_at' => now(),
        ]);
    }
}
