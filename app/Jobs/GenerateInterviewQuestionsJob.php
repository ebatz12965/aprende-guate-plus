<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\InterviewStage;
use App\Services\Interviews\InterviewConversation;
use App\Services\Interviews\InterviewQuestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Prepara el banco de preguntas de una entrevista y abre la conversación con el saludo y la primera pregunta. */
class GenerateInterviewQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $stageId)
    {
        $this->timeout = AiGateway::callTimeout((int) config('ai.content_timeout')) + 20;
    }

    public function handle(InterviewQuestionService $questions, InterviewConversation $conversation): void
    {
        $stage = InterviewStage::with('simulation')->find($this->stageId);
        if (! $stage || $stage->status !== 'preparing') {
            return;
        }

        $mode = $stage->content['mode'] ?? 'chat';
        $credit = (bool) ($stage->content['voice_credit'] ?? false);
        $bank = $questions->bank($stage->simulation, $stage);
        DB::transaction(function () use ($stage, $bank, $mode, $credit, $conversation) {
            $stage->turns()->delete();
            $stage->update(['content' => $bank + ['mode' => $mode, 'voice_credit' => $credit]]);
            $this->open($stage, $mode, $conversation);
            if ($stage->simulation->status === 'ready') {
                $stage->simulation->update(['status' => 'in_progress']);
            }
        });
    }

    /** Si algo falla, la entrevista igual empieza con las preguntas de respaldo. */
    public function failed(?Throwable $exception): void
    {
        $stage = InterviewStage::with('simulation')->find($this->stageId);
        if (! $stage || $stage->status !== 'preparing') {
            return;
        }
        $mode = $stage->content['mode'] ?? 'chat';
        $credit = (bool) ($stage->content['voice_credit'] ?? false);
        $stage->turns()->delete();
        $stage->update(['content' => app(InterviewQuestionService::class)->fallback($stage->simulation, $stage) + ['source' => 'plantilla', 'mode' => $mode, 'voice_credit' => $credit]]);
        $this->open($stage, $mode, app(InterviewConversation::class));
    }

    /** Por voz, la conversación sucede en la llamada; por chat, se abre con el saludo y la primera pregunta. */
    private function open(InterviewStage $stage, string $mode, InterviewConversation $conversation): void
    {
        $mode === 'voice'
            ? $stage->update(['status' => 'in_progress', 'started_at' => now()])
            : $conversation->begin($stage->refresh());
    }
}
