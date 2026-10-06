<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\InterviewStage;
use App\Services\Interviews\PracticalTestService;
use App\Services\Interviews\ReasoningTestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Prepara la prueba de razonamiento o la prueba práctica. El tiempo empieza a contar cuando la persona la abre. */
class GenerateInterviewTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $stageId)
    {
        $this->timeout = AiGateway::callTimeout((int) config('ai.content_timeout')) + 20;
    }

    public function handle(ReasoningTestService $reasoning, PracticalTestService $practical): void
    {
        $stage = InterviewStage::with('simulation')->find($this->stageId);
        if (! $stage || $stage->status !== 'preparing') {
            return;
        }

        $content = $stage->type === 'reasoning_test'
            ? $reasoning->build($stage->simulation, $stage)
            : $practical->build($stage->simulation, $stage);
        $stage->update(['content' => $content, 'status' => 'in_progress', 'started_at' => null]);
        if ($stage->simulation->status === 'ready') {
            $stage->simulation->update(['status' => 'in_progress']);
        }
    }

    public function failed(?Throwable $exception): void
    {
        InterviewStage::whereKey($this->stageId)->where('status', 'preparing')->update([
            'status' => 'available',
            'content' => json_encode(['error' => 'No pudimos preparar la prueba. Inténtalo de nuevo.']),
        ]);
    }
}
