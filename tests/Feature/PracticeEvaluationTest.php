<?php

namespace Tests\Feature;

use App\Jobs\EvaluatePracticeAttemptJob;
use App\Models\LearningPath;
use App\Models\LearningPathPracticeAttempt;
use App\Models\LearningPathTopic;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PracticeEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private function topicWithPractice(User $user): LearningPathTopic
    {
        $category = SkillCategory::create(['slug' => 'ofimatica', 'name' => 'Ofimática']);
        Skill::create(['skill_category_id' => $category->id, 'slug' => 'ofimatica.tablas', 'name' => 'Tablas']);
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => 'Excel', 'status' => 'active']);
        $module = $path->items()->create(['title' => 'Módulo', 'position' => 1, 'quiz' => ['question' => 'P', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
        $topic = $module->topics()->create(['title' => 'Tablas dinámicas', 'learning_objective' => 'Resumir datos.', 'practice_type' => 'case_study', 'position' => 1, 'metadata' => ['skill_slug' => 'ofimatica.tablas']]);
        $topic->practice()->create(['type' => 'case_study', 'title' => 'Caso', 'instructions' => ['context' => 'Contexto', 'steps' => ['Uno', 'Dos', 'Tres']], 'artifact' => ['label' => 'Caso', 'language' => 'text', 'content' => 'Datos del caso'], 'expected_solution' => ['criteria' => 'Resume por categoría.', 'hint' => 'Usa una tabla dinámica.'], 'generated_at' => now()]);

        return $topic;
    }

    private function submit(User $user, LearningPathTopic $topic): LearningPathPracticeAttempt
    {
        $this->actingAs($user)->post(route('adaptive.topic.practice.submit', $topic), ['answer' => 'Creé una tabla dinámica por categoría y comparé totales.'])->assertSessionHas('success');

        return LearningPathPracticeAttempt::firstOrFail();
    }

    private function evaluatorAnswers(array $evaluation): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode($evaluation)]]]])]);
    }

    public function test_submission_completes_the_topic_and_queues_the_evaluation(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $topic = $this->topicWithPractice($user);

        $attempt = $this->submit($user, $topic);

        $this->assertSame('queued', $attempt->status);
        $this->assertSame('completed', $topic->fresh()->status);
        $this->assertSame(0.0, (float) UserSkill::firstOrFail()->confidence);
        Queue::assertPushed(EvaluatePracticeAttemptJob::class, fn ($job) => $job->attemptId === $attempt->id);
    }

    public function test_evaluation_stores_feedback_and_sets_skill_confidence_from_real_scores(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $topic = $this->topicWithPractice($user);
        $attempt = $this->submit($user, $topic);
        $this->evaluatorAnswers(['score' => 72, 'summary' => 'Buen resumen por categoría.', 'strengths' => ['Comparaste totales'], 'improvements' => ['Agrega un gráfico']]);

        app()->call([new EvaluatePracticeAttemptJob($attempt->id), 'handle']);

        $attempt->refresh();
        $this->assertSame('evaluated', $attempt->status);
        $this->assertSame(72, $attempt->score);
        $skill = UserSkill::firstOrFail();
        $this->assertSame(72.0, (float) $skill->confidence);
        $this->assertSame(2, (int) $skill->level);
        $this->assertDatabaseHas('skill_evidences', ['source_id' => $attempt->id, 'score' => 72]);

        $this->actingAs($user)->get(route('adaptive.topic.show', $topic))
            ->assertSee('Buen resumen por categoría.')
            ->assertSee('Agrega un gráfico');
    }

    public function test_invalid_or_missing_evaluation_keeps_the_submission_without_score(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $topic = $this->topicWithPractice($user);
        $attempt = $this->submit($user, $topic);
        $this->evaluatorAnswers(['score' => 140, 'summary' => 'Puntaje imposible', 'strengths' => [], 'improvements' => []]);

        app()->call([new EvaluatePracticeAttemptJob($attempt->id), 'handle']);

        $this->assertSame('requires_ai', $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->score);
        $this->assertSame('completed', $topic->fresh()->status);
    }
}
