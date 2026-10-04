<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\ChallengeSubmission;
use App\Services\Learning\ChallengeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EvaluateChallengeSubmissionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $submissionId)
    {
        $this->timeout = AiGateway::jobTimeout(1);
    }

    public function handle(ChallengeService $challenges): void
    {
        $submission = ChallengeSubmission::with('challenge')->find($this->submissionId);
        if (! $submission || ! in_array($submission->status, ['queued', 'requires_ai'], true)) {
            return;
        }
        $submission->update(['status' => 'evaluating']);
        try {
            $excerpt = $this->readInspectableExcerpt($submission);
            $evaluation = $challenges->evaluate($submission->challenge, $submission, $excerpt);
            if (! data_get($evaluation, 'generated_by_ai')) {
                $submission->update(['status' => 'requires_ai', 'attachment_analysis' => $excerpt ? 'Se preparó una vista previa de texto para evaluación.' : $this->attachmentNote($submission), 'feedback' => 'La evidencia se guardó correctamente. La evaluación automática se realizará cuando el agente IA esté disponible.']);

                return;
            }
            $submission->update(['status' => 'evaluated', 'score' => data_get($evaluation, 'score'), 'feedback' => data_get($evaluation, 'summary'), 'evaluation' => $evaluation, 'attachment_analysis' => $excerpt ? 'El agente revisó una vista previa de texto del archivo adjunto.' : $this->attachmentNote($submission), 'evaluated_at' => now()]);
        } catch (Throwable $exception) {
            $submission->update(['status' => 'evaluation_failed', 'feedback' => 'La evidencia se guardó, pero no se pudo evaluar todavía. Puedes solicitar una nueva evaluación más tarde.']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        ChallengeSubmission::whereKey($this->submissionId)->update(['status' => 'evaluation_failed', 'feedback' => 'La evidencia se guardó, pero no se pudo evaluar todavía.']);
    }

    private function readInspectableExcerpt(ChallengeSubmission $submission): ?string
    {
        if (! $submission->attachment_path || ! in_array($submission->attachment_mime, ['text/plain', 'text/csv', 'application/csv'], true) || ! Storage::disk('local')->exists($submission->attachment_path)) {
            return null;
        }

        return mb_substr((string) Storage::disk('local')->get($submission->attachment_path), 0, 12000);
    }

    private function attachmentNote(ChallengeSubmission $submission): ?string
    {
        if (! $submission->attachment_path) {
            return null;
        }

        return 'Archivo privado recibido ('.$submission->attachment_name.'). Este tipo de archivo requiere un analizador especializado para revisión de contenido.';
    }
}
