<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\AiContentVersion;
use App\Models\LearningPathTopic;
use App\Models\LibraryTopic;
use App\Services\Learning\TopicContentService;
use App\Services\Library\LibraryContentService;
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
        // Clasificación del tema + borrador, reintento y revisión de la lección o práctica.
        $this->timeout = AiGateway::jobTimeout(1) + AiGateway::callTimeout((int) config('ai.content_timeout')) * 3;
    }

    public function handle(TopicContentService $content, LibraryContentService $library): void
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
            // Temas de la biblioteca compartida (Office): se toma el contenido ya generado o se genera una sola vez para todos.
            $libraryTopic = ($id = data_get($topic->metadata, 'library_topic_id')) ? LibraryTopic::find($id) : null;
            if (! $libraryTopic) {
                $contentPlan = $content->classify($path, $topic);
                $topic->update(['metadata' => array_merge($topic->metadata ?? [], ['content_plan' => $contentPlan])]);
                $topic->refresh();
            }

            if ($this->type === 'lesson') {
                $lesson = $libraryTopic ? $library->lesson($libraryTopic) : $content->generateLesson($path, $topic);
                $generatedLesson = $topic->lesson()->firstOrCreate([], [
                    'content' => ['version' => 2] + ($libraryTopic ? ['library_topic_id' => $libraryTopic->id] : []) + collect($lesson)->except('youtube_queries')->all(),
                    'resources' => ['youtube_queries' => $lesson['youtube_queries']],
                    // Compatibilidad: la primera pregunta de comprobación también queda como quiz del tema.
                    'quiz' => $lesson['check_questions'][0] ?? null,
                    'generated_at' => now(),
                ]);
                AiContentVersion::firstOrCreate(['content_type' => 'lesson', 'content_id' => $generatedLesson->id, 'version' => 1], ['payload' => ['content' => $generatedLesson->content, 'resources' => $generatedLesson->resources, 'quiz' => $generatedLesson->quiz]]);
            } else {
                $practice = $libraryTopic ? $library->practice($libraryTopic) : $content->generatePractice($path, $topic);
                $generatedPractice = $topic->practice()->firstOrCreate([], [
                    'type' => $topic->practice_type,
                    'title' => $practice['title'],
                    'instructions' => [
                        'version' => 2,
                        'context' => $practice['context'],
                        'objective' => $practice['objective'],
                        'steps' => $practice['instructions'],
                        'deliverable' => $practice['deliverable'],
                        'success_criteria' => $practice['success_criteria'],
                        'hints' => $practice['hints'],
                        'self_check' => $practice['self_check'],
                    ],
                    'artifact' => $practice['material'],
                    // La solución de referencia solo la usa el evaluador; nunca se muestra.
                    'expected_solution' => ['criteria' => implode(' ', $practice['success_criteria']), 'hint' => $practice['hints'][0] ?? null, 'reference' => $practice['reference_solution'], 'results' => $practice['computed_results'] ?? []],
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
