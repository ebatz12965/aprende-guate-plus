<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\InterviewStage;
use App\Services\Interviews\InterviewEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Evalúa una entrevista ya entregada. Si el evaluador no responde, queda pendiente con la opción de reintentar. */
class EvaluateInterviewStageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $stageId)
    {
        $this->timeout = AiGateway::callTimeout((int) config('ai.content_timeout')) + 20;
    }

    public function handle(InterviewEvaluationService $evaluator): void
    {
        $stage = InterviewStage::with(['simulation', 'turns'])->find($this->stageId);
        if (! $stage || $stage->status !== 'submitted') {
            return;
        }

        $evaluation = $evaluator->evaluate($stage);
        if (! $evaluation) {
            $stage->update(['content' => ['evaluation_status' => 'failed'] + $stage->content]);

            return;
        }

        $stage->update([
            'status' => 'evaluated',
            'score' => $evaluation['score'],
            'evaluation' => $evaluation,
            'evaluated_at' => now(),
            'content' => ['evaluation_status' => 'done'] + $stage->content,
        ]);

        // Si el proceso ya estaba completo, el puntaje general incluye esta evaluación.
        $simulation = $stage->simulation;
        if ($simulation->status === 'completed') {
            $scores = $simulation->stages()->whereNotNull('score')->pluck('score');
            $simulation->update(['overall_score' => $scores->isEmpty() ? null : (int) round($scores->avg())]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $stage = InterviewStage::find($this->stageId);
        if ($stage && $stage->status === 'submitted') {
            $stage->update(['content' => ['evaluation_status' => 'failed'] + ($stage->content ?? [])]);
        }
    }
}
