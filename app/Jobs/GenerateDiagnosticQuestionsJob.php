<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\DiagnosticAttempt;
use App\Services\Learning\DiagnosticQuestionService;
use App\Services\Learning\GoalAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Analiza la meta y arma el cuestionario de nivel a partir de ese análisis. */
class GenerateDiagnosticQuestionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $attemptId)
    {
        // Análisis (borrador, reintento y revisión) + cuestionario (borrador, reintento y revisión).
        $this->timeout = AiGateway::callTimeout((int) config('ai.content_timeout')) * 6 + 20;
    }

    public function handle(GoalAnalysisService $analyst, DiagnosticQuestionService $diagnostic): void
    {
        $attempt = DiagnosticAttempt::find($this->attemptId);
        if (! $attempt || $attempt->status !== 'preparing') {
            return;
        }

        $analysis = $analyst->analyze($attempt->primary_answer, $attempt->user_id);
        $attempt->update(['analysis' => $analysis]);

        $attempt->update([
            'questions' => $diagnostic->generate($attempt->primary_answer, $analysis, $attempt->user_id),
            'status' => 'started',
        ]);
    }

    /** Si el job falla, el estudiante continúa con un cuestionario de respaldo en lugar de quedarse esperando. */
    public function failed(?Throwable $exception): void
    {
        $attempt = DiagnosticAttempt::whereKey($this->attemptId)->where('status', 'preparing')->first();
        if (! $attempt) {
            return;
        }
        $analysis = $attempt->analysis ?: app(GoalAnalysisService::class)->fallback($attempt->primary_answer);
        $attempt->update(['analysis' => $analysis, 'questions' => app(DiagnosticQuestionService::class)->fallbackQuestions($analysis), 'status' => 'started']);
    }
}
