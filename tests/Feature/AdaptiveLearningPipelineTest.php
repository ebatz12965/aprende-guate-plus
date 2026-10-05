<?php

namespace Tests\Feature;

use App\Jobs\GenerateLearningPathJob;
use App\Jobs\GenerateTopicContentJob;
use App\Models\DiagnosticAttempt;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdaptiveLearningPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostic_creates_a_granular_architecture_instead_of_three_complete_phases(): void
    {
        Http::fake();
        Queue::fake();
        $user = User::factory()->create();
        $attempt = DiagnosticAttempt::create([
            'user_id' => $user->id,
            'primary_answer' => 'Quiero conseguir trabajo como desarrollador web',
            'questions' => [['question' => 'Experiencia', 'options' => ['Nada', 'Algo', 'Mucho']], ['question' => 'Preferencia', 'options' => ['Base', 'Práctica', 'Proyecto']], ['question' => 'Tiempo', 'options' => ['1h', '3h', '5h']], ['question' => 'Estilo', 'options' => ['Guía', 'Reto', 'Caso']]],
            'answers' => [],
            'status' => 'started',
        ]);

        $this->actingAs($user)->post(route('adaptive.diagnostic.store', $attempt), ['answers' => [0, 1, 1, 2]])
            ->assertRedirect(route('adaptive.diagnostic.generation', $attempt));
        Queue::assertPushed(GenerateLearningPathJob::class, fn ($job) => $job->attemptId === $attempt->id);
        app()->call([new GenerateLearningPathJob($attempt->id), 'handle']);

        $attempt->refresh();
        $this->assertSame('completed', $attempt->status);
        $path = LearningPath::where('user_id', $user->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(5, $path->items()->count());
        $this->assertDatabaseHas('learning_path_topics', ['learning_path_item_id' => $path->items()->first()->id]);
        $this->assertSame('pipeline-v1', $path->generation_version);
    }

    public function test_office_topic_generates_and_downloads_a_csv_practice(): void
    {
        Http::fake();
        Queue::fake();
        [$user, $topic] = $this->topicFor('Mejorar el manejo de Office', 'excel_dataset');
        $this->actingAs($user)->post(route('adaptive.topic.practice', $topic))->assertRedirect();
        Queue::assertPushed(GenerateTopicContentJob::class, fn ($job) => $job->topicId === $topic->id && $job->type === 'practice');
        app()->call([new GenerateTopicContentJob($topic->id, 'practice'), 'handle']);

        $this->assertDatabaseHas('learning_path_practices', ['learning_path_topic_id' => $topic->id, 'type' => 'excel_dataset']);
        $this->actingAs($user)->get(route('adaptive.topic.practice.download', $topic))->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('ID_Venta');
    }

    public function test_programming_topic_generates_a_code_artifact_and_records_submission(): void
    {
        Http::fake();
        Queue::fake();
        [$user, $topic] = $this->topicFor('Conseguir trabajo de Desarrollador Web', 'code_bug');
        $this->actingAs($user)->post(route('adaptive.topic.practice', $topic))->assertRedirect();
        Queue::assertPushed(GenerateTopicContentJob::class, fn ($job) => $job->topicId === $topic->id && $job->type === 'practice');
        app()->call([new GenerateTopicContentJob($topic->id, 'practice'), 'handle']);

        $this->actingAs($user)->get(route('adaptive.topic.show', $topic))->assertOk()->assertSee('function validarCorreo');
        $this->actingAs($user)->post(route('adaptive.topic.practice.submit', $topic), ['answer' => 'Corregí la función para validar el correo.'])->assertSessionHas('success');

        $this->assertDatabaseHas('learning_path_practice_attempts', ['user_id' => $user->id, 'answer' => 'Corregí la función para validar el correo.']);
        $this->assertDatabaseHas('learning_path_topics', ['id' => $topic->id, 'status' => 'completed']);
    }

    private function topicFor(string $goal, string $practiceType): array
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => $goal, 'status' => 'active']);
        $module = $path->items()->create(['title' => 'Módulo inicial', 'area' => 'Base', 'description' => 'Descripción', 'position' => 1, 'quiz' => ['question' => 'Pregunta', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
        $topic = $module->topics()->create(['title' => 'Tema práctico', 'learning_objective' => 'Aplicar una habilidad.', 'practice_type' => $practiceType, 'position' => 1]);

        return [$user, $topic];
    }
}
