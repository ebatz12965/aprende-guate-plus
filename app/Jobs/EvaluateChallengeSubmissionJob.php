<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\ChallengeSubmission;
use App\Services\Learning\ChallengeService;
use App\Services\Learning\EvidenceTextExtractor;
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

    public function handle(ChallengeService $challenges, EvidenceTextExtractor $extractor): void
    {
        $submission = ChallengeSubmission::with('challenge')->find($this->submissionId);
        if (! $submission || ! in_array($submission->status, ['queued', 'requires_ai'], true)) {
            return;
        }
        $submission->update(['status' => 'evaluating']);
        try {
            $excerpt = $this->readInspectableExcerpt($submission, $extractor);
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

    private function readInspectableExcerpt(ChallengeSubmission $submission, EvidenceTextExtractor $extractor): ?string
    {
        if (! $submission->attachment_path || ! Storage::disk('local')->exists($submission->attachment_path)) {
            return null;
        }

        return $extractor->extract(Storage::disk('local')->path($submission->attachment_path), $submission->attachment_name, $submission->attachment_mime);
    }

    private function attachmentNote(ChallengeSubmission $submission): ?string
    {
        if (! $submission->attachment_path) {
            return null;
        }

        return 'Archivo privado recibido ('.$submission->attachment_name.'). No se pudo extraer texto de este archivo (las imágenes, ZIP y formatos antiguos de Office no se analizan); el evaluador usó tu respuesta escrita y la metodología.';
    }
}
