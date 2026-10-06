<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\LearningPathTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_saved_and_offered_back_when_returning(): void
    {
        [$user, $topic] = $this->topicWithLesson();

        $this->actingAs($user)->putJson(route('adaptive.topic.lesson.progress', $topic), $this->progress($topic, 3, [0 => 2]))->assertOk();

        $saved = $topic->fresh()->metadata['lesson_progress'];
        $this->assertSame(3, $saved['i']);
        $this->assertSame([0 => 2], $saved['picked']);
        $this->assertArrayHasKey('saved_at', $saved);

        // La vista recibe el avance para ofrecer "Continuar donde lo dejé" en cualquier dispositivo.
        $this->actingAs($user)->get(route('adaptive.topic.show', $topic))->assertOk()
            ->assertSee('Tienes esta lección a medias')
            ->assertSee('Continuar donde lo dejé')
            ->assertSee('Empezar desde el inicio')
            ->assertSee('saved_at', false);
    }

    public function test_progress_from_a_regenerated_lesson_is_rejected(): void
    {
        [$user, $topic] = $this->topicWithLesson();

        $body = $this->progress($topic, 2);
        $body['lesson_id'] = $topic->lesson->id + 99;
        $this->actingAs($user)->putJson(route('adaptive.topic.lesson.progress', $topic), $body)->assertStatus(409);
        $this->assertArrayNotHasKey('lesson_progress', $topic->fresh()->metadata ?? []);
    }

    public function test_nobody_else_can_write_your_progress(): void
    {
        [, $topic] = $this->topicWithLesson();

        // Otra persona recibe 404: ni siquiera se revela que el tema existe.
        $this->actingAs(User::factory()->create())->putJson(route('adaptive.topic.lesson.progress', $topic), $this->progress($topic, 1))->assertNotFound();
        $this->assertArrayNotHasKey('lesson_progress', $topic->fresh()->metadata ?? []);
    }

    private function progress(LearningPathTopic $topic, int $slide, array $picked = []): array
    {
        return ['lesson_id' => $topic->lesson->id, 'i' => $slide, 'visited' => [0, 1], 'examples' => [], 'flipped' => [], 'fixes' => [], 'steps' => 0, 'decision' => null, 'picked' => $picked, 'asText' => false];
    }

    private function topicWithLesson(): array
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => 'Meta', 'status' => 'active']);
        $module = $path->items()->create(['title' => 'Fase', 'area' => 'Base', 'description' => 'Fase', 'position' => 1, 'status' => 'in_progress', 'quiz' => ['question' => 'Q', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
        $topic = $module->topics()->create(['title' => 'Tema', 'learning_objective' => 'Aprender.', 'practice_type' => 'case_study', 'position' => 1]);
        $section = ['heading' => 'Parte', 'explanation' => 'Texto.', 'example' => 'Ejemplo.'];
        $topic->lesson()->create(['content' => ['version' => 2, 'intro' => 'Intro', 'objectives' => ['Uno'], 'sections' => [$section, $section], 'key_concepts' => [], 'common_mistakes' => [], 'check_questions' => [['question' => '¿Q?', 'options' => ['A', 'B', 'C', 'D'], 'answer' => 1, 'explanation' => 'Porque sí.']], 'summary' => 'Resumen'], 'resources' => ['youtube_queries' => []], 'generated_at' => now()]);

        return [$user, LearningPathTopic::find($topic->id)];
    }
}
