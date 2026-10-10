<?php

namespace App\Services\Library;

use App\Jobs\GenerateLibraryWalkthroughJob;
use App\Models\LearningPath;
use App\Models\LearningPathTopic;
use App\Models\LibraryTopic;
use App\Services\Learning\TopicContentService;
use App\Support\Library\OfficeSyllabus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Biblioteca compartida: genera la lección y la práctica de cada tema una sola vez y las entrega a quien las necesite.
 * Si dos personas abren el mismo tema a la vez, la segunda espera a que termine la primera en lugar de generar otra copia.
 */
class LibraryContentService
{
    /** Duración del bloqueo mientras se genera un tema (borrador, reintento y revisión). */
    public const LOCK_SECONDS = 300;

    /** Espera máxima por un tema que otra persona está generando; menor que el timeout del job que espera. */
    public const WAIT_SECONDS = 240;

    public function __construct(private readonly TopicContentService $content) {}

    /** Crea o actualiza los temas del temario en la base; no toca el contenido ya generado. */
    public function sync(): int
    {
        $count = 0;
        foreach (OfficeSyllabus::topics() as $tool => $levels) {
            foreach ($levels as $level => $topics) {
                foreach ($topics as $index => [$title, $objective, $practiceType]) {
                    LibraryTopic::updateOrCreate(['slug' => OfficeSyllabus::COURSE.'.'.$tool.'.'.$level.'.'.Str::slug($title)], [
                        'course' => OfficeSyllabus::COURSE, 'tool' => $tool, 'level' => $level, 'level_order' => OfficeSyllabus::LEVELS[$level]['order'],
                        'position' => $index + 1, 'title' => $title, 'learning_objective' => $objective, 'practice_type' => $practiceType,
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    /** @return array<string, mixed> */
    public function lesson(LibraryTopic $topic, bool $queueWalkthrough = true): array
    {
        return $this->once($topic, 'lesson', function () use ($topic, $queueWalkthrough) {
            [$path, $context] = $this->context($topic);
            $lesson = $this->content->generateLesson($path, $context);
            $this->ensureGenerated($topic);
            $topic->update(['lesson' => $lesson, 'lesson_generated_at' => now()]);
            // El extracto animado parte del ejemplo resuelto de la lección; se prepara aparte para no alargar la espera.
            if ($queueWalkthrough) {
                GenerateLibraryWalkthroughJob::dispatch($topic->id)->onQueue('ai');
            }
        });
    }

    /** @return array<string, mixed> */
    public function practice(LibraryTopic $topic): array
    {
        return $this->once($topic, 'practice', function () use ($topic) {
            [$path, $context] = $this->context($topic);
            $practice = $this->content->generatePractice($path, $context);
            $this->ensureGenerated($topic);
            $topic->update(['practice' => $practice, 'practice_generated_at' => now()]);
        });
    }

    /**
     * Ruta y tema sin guardar que describen el tema de la biblioteca para los generadores de contenido.
     *
     * @return array{0: LearningPath, 1: LearningPathTopic}
     */
    public function context(LibraryTopic $topic): array
    {
        $siblings = LibraryTopic::where('course', $topic->course)->where('tool', $topic->tool)->where('level', $topic->level)->orderBy('position')->pluck('title', 'position');
        $earlier = LibraryTopic::where('course', $topic->course)->where('tool', $topic->tool)->where('level_order', '<', $topic->level_order)->count();
        $path = new LearningPath(['title' => 'Microsoft Office de principiante a experto', 'goal' => 'Dominar Microsoft '.$topic->toolLabel().' para el trabajo (Microsoft Office)', 'estimated_level' => $topic->levelLabel()]);
        $context = 'Contexto: biblioteca del curso "Microsoft Office de principiante a experto", usada por muchas personas en Guatemala; '
            .'herramienta: Microsoft '.$topic->toolLabel().' (Microsoft 365 en español, versión de escritorio para Windows); nivel: '.$topic->levelLabel().'; '
            .($earlier ? 'la persona ya domina los '.$earlier.' temas de los niveles anteriores de '.$topic->toolLabel().'; ' : 'la persona nunca ha usado '.$topic->toolLabel().'; ')
            .'tema '.$topic->position.' de '.$siblings->count().' del nivel: "'.$topic->title.'"; objetivo del tema: "'.$topic->learning_objective.'"; '
            .'tipo de práctica: '.$topic->practice_type.'; '
            .'temas anteriores del nivel: '.($siblings->filter(fn ($title, $position) => $position < $topic->position)->implode('; ') ?: 'ninguno').'; '
            .'temas posteriores del nivel (no los adelantes): '.($siblings->filter(fn ($title, $position) => $position > $topic->position)->implode('; ') ?: 'ninguno').'. '
            .'Escribe para cualquier persona que aprende '.$topic->toolLabel().', sin suponer su profesión: usa situaciones de oficina comunes en Guatemala.';

        $learningTopic = new LearningPathTopic(['title' => $topic->title, 'learning_objective' => $topic->learning_objective, 'practice_type' => $topic->practice_type, 'position' => $topic->position]);
        $learningTopic->metadata = ['library_context' => $context, 'content_plan' => ['content_domain' => 'ofimatica', 'lesson_format' => 'demostracion_guiada', 'interaction' => 'paso_a_paso_con_verificacion']];

        return [$path, $learningTopic];
    }

    /** El contenido de respaldo es genérico: sirve para una persona, pero no se guarda en la biblioteca para todas. */
    private function ensureGenerated(LibraryTopic $topic): void
    {
        if ($this->content->usedFallback) {
            throw new \RuntimeException("La IA no generó un contenido válido para el tema de biblioteca {$topic->slug}.");
        }
    }

    /**
     * @param  callable(): void  $generate
     * @return array<string, mixed>
     */
    private function once(LibraryTopic $topic, string $part, callable $generate): array
    {
        if ($topic->{$part}) {
            return $topic->{$part};
        }

        return Cache::lock("library-topic-{$topic->id}-{$part}", self::LOCK_SECONDS)->block(self::WAIT_SECONDS, function () use ($topic, $part, $generate) {
            // Pudo terminarla otra persona mientras esperábamos.
            $topic->refresh();
            if (! $topic->{$part}) {
                $generate();
            }

            return $topic->{$part};
        });
    }
}
