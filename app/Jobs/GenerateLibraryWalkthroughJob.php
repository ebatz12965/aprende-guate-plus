<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\LibraryTopic;
use App\Services\Library\WalkthroughService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Prepara una sola vez el extracto animado (guion, resultados y narración) de un tema de la biblioteca. */
class GenerateLibraryWalkthroughJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $libraryTopicId, public bool $force = false)
    {
        // Borrador, reintento y revisión del guion, más hasta 10 audios de narración.
        $this->timeout = AiGateway::callTimeout((int) config('ai.content_timeout')) * 3 + 10 * (int) config('services.elevenlabs.timeout', 30) + 20;
    }

    public function handle(WalkthroughService $walkthroughs): void
    {
        $topic = LibraryTopic::find($this->libraryTopicId);
        if (! $topic || ! $topic->lesson || ($topic->hasWalkthrough() && ! $this->force)) {
            return;
        }

        $walkthrough = $walkthroughs->generate($topic);
        if ($walkthrough) {
            $topic->update(['walkthrough' => $walkthrough, 'walkthrough_generated_at' => now()]);
        }
    }
}
