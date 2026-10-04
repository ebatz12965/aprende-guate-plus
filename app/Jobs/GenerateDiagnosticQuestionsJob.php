<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\DiagnosticAttempt;
use App\Services\Learning\DiagnosticQuestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Genera las preguntas del diagnóstico fuera de la petición web; la página espera con recarga automática. */
class GenerateDiagnosticQuestionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $attemptId)
    {
        $this->timeout = AiGateway::jobTimeout(1);
    }

    public function handle(DiagnosticQuestionService $diagnostic): void
    {
        $attempt = DiagnosticAttempt::find($this->attemptId);
        if (! $attempt || $attempt->status !== 'preparing') {
            return;
        }

        $attempt->update([
            'questions' => $diagnostic->generate($attempt->primary_answer, $attempt->user_id),
            'status' => 'started',
        ]);
    }

    /** Si el job falla, el estudiante continúa con las preguntas genéricas en lugar de quedarse esperando. */
    public function failed(?Throwable $exception): void
    {
        DiagnosticAttempt::whereKey($this->attemptId)->where('status', 'preparing')->update([
            'questions' => json_encode(app(DiagnosticQuestionService::class)->fallbackQuestions(), JSON_UNESCAPED_UNICODE),
            'status' => 'started',
        ]);
    }
}
