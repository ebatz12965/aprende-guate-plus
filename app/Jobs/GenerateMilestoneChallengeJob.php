<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\Challenge;
use App\Models\LearningPath;
use App\Services\Learning\ChallengeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateMilestoneChallengeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $learningPathId, public int $milestone)
    {
        $this->timeout = AiGateway::jobTimeout(1);
    }

    public function handle(ChallengeService $challenges): void
    {
        $path = LearningPath::with(['items.topics', 'user'])->find($this->learningPathId);
        if (! $path || ! in_array($this->milestone, [25, 50, 75, 100], true)) {
            return;
        }

        $challenge = Challenge::firstOrCreate(
            ['user_id' => $path->user_id, 'learning_path_id' => $path->id, 'milestone' => $this->milestone],
            ['title' => 'Reto práctico del '.$this->milestone.'%', 'area' => 'Ruta personalizada', 'level' => $path->estimated_level ?: 'Inicial', 'description' => 'Estamos preparando un reto basado en tu avance.', 'generation_status' => 'queued']
        );

        if ($challenge->generation_status === 'completed') {
            return;
        }

        $challenge->update(['generation_status' => 'processing']);

        try {
            $content = $challenges->generateForMilestone($path, $this->milestone);
            $challenge->update(array_merge($content, [
                'generation_status' => 'completed',
                'generated_at' => now(),
            ]));
        } catch (Throwable $exception) {
            $challenge->update(['generation_status' => 'failed']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Challenge::where('learning_path_id', $this->learningPathId)
            ->where('milestone', $this->milestone)
            ->update(['generation_status' => 'failed']);
    }
}
