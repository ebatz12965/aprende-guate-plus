<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\LearningPathTopic;
use App\Models\AiContentVersion;
use App\Services\AiTutorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateTopicContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $topicId, public string $type)
    {
        // Clasificación del tema + lección o práctica.
        $this->timeout = AiGateway::jobTimeout(2);
    }

    public function handle(AiTutorService $tutor): void
    {
        $topic = LearningPathTopic::with('module.learningPath')->find($this->topicId);

        if (! $topic || ! in_array($this->type, ['lesson', 'practice'], true)) {
            return;
        }

        $relation = $this->type;
        $statusColumn = "{$this->type}_generation_status";

        if ($topic->{$relation}) {
            $topic->update([$statusColumn => 'completed', 'generation_error' => null]);

            return;
        }

        $topic->update([$statusColumn => 'processing', 'generation_error' => null]);

        try {
            $path = $topic->module->learningPath;
            $contentPlan = app(AiTutorService::class)->classifyTopicContent($path, $topic);
            $topic->update(['metadata' => array_merge($topic->metadata ?? [], ['content_plan' => $contentPlan])]);
            $topic->refresh();

            if ($this->type === 'lesson') {
                $lesson = $tutor->generateLesson($path, $topic);
                $generatedLesson = $topic->lesson()->firstOrCreate([], [
                    'content' => ['summary' => $lesson['summary'], 'key_concepts' => $lesson['key_concepts'], 'steps' => $lesson['steps']],
                    'resources' => ['youtube_queries' => $lesson['youtube_queries']],
                    'quiz' => $lesson['quiz'],
                    'generated_at' => now(),
                ]);
                AiContentVersion::firstOrCreate(['content_type' => 'lesson', 'content_id' => $generatedLesson->id, 'version' => 1], ['payload' => ['content' => $generatedLesson->content, 'resources' => $generatedLesson->resources, 'quiz' => $generatedLesson->quiz]]);
            } else {
                $practice = $tutor->generatePractice($path, $topic);
                $generatedPractice = $topic->practice()->firstOrCreate([], [
                    'type' => $topic->practice_type,
                    'title' => $practice['title'],
                    'instructions' => ['context' => $practice['context'], 'steps' => $practice['instructions']],
                    'artifact' => ['label' => $practice['artifact_label'], 'language' => $practice['artifact_language'], 'content' => $practice['artifact_content']],
                    'expected_solution' => $practice['expected_solution'],
                    'generated_at' => now(),
                ]);
                AiContentVersion::firstOrCreate(['content_type' => 'practice', 'content_id' => $generatedPractice->id, 'version' => 1], ['payload' => ['instructions' => $generatedPractice->instructions, 'artifact' => $generatedPractice->artifact, 'expected_solution' => $generatedPractice->expected_solution]]);
            }

            $topic->update([$statusColumn => 'completed', 'generation_error' => null]);
        } catch (Throwable $exception) {
            $topic->update([
                $statusColumn => 'failed',
                'generation_error' => 'No se pudo generar este contenido. Puedes intentarlo nuevamente.',
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        LearningPathTopic::whereKey($this->topicId)->update([
            "{$this->type}_generation_status" => 'failed',
            'generation_error' => 'No se pudo generar este contenido. Puedes intentarlo nuevamente.',
        ]);
    }
}
