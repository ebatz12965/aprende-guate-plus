<?php

namespace Tests\Feature;

use App\Jobs\GenerateDiagnosticQuestionsJob;
use App\Jobs\GenerateMentorQuestionnaireJob;
use App\Models\DiagnosticAttempt;
use App\Models\User;
use App\Services\MentorQuestionnaireDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class AsyncAiFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostic_questions_are_generated_in_the_background(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('adaptive.diagnostic.start'), ['primary_answer' => 'Quiero aprender Excel para mi trabajo'])
            ->assertRedirect();

        $attempt = DiagnosticAttempt::firstOrFail();
        $this->assertSame('preparing', $attempt->status);
        Queue::assertPushedOn('ai', GenerateDiagnosticQuestionsJob::class);
        $this->actingAs($user)->get(route('adaptive.diagnostic.questions', $attempt))->assertOk()->assertSee('Estamos analizando tu meta');

        app()->call([new GenerateDiagnosticQuestionsJob($attempt->id), 'handle']);

        // Sin IA disponible: análisis por palabras clave y autoevaluación por habilidad.
        $attempt->refresh();
        $this->assertSame('started', $attempt->status);
        $this->assertSame('ofimatica', $attempt->analysis['domain']);
        $this->assertSame(['context', 'context'], array_column(array_slice($attempt->questions, 0, 2), 'kind'));
        $this->assertSame(['self'], array_values(array_unique(array_column(array_slice($attempt->questions, 2), 'kind'))));
        $this->actingAs($user)->get(route('adaptive.diagnostic.questions', $attempt))->assertOk()->assertDontSee('Estamos analizando tu meta')->assertSee('Esto entendimos de tu meta');
    }

    public function test_failed_diagnostic_job_falls_back_to_generic_questions(): void
    {
        $attempt = DiagnosticAttempt::create(['user_id' => User::factory()->create()->id, 'primary_answer' => 'Excel', 'answers' => [], 'status' => 'preparing']);

        (new GenerateDiagnosticQuestionsJob($attempt->id))->failed(new RuntimeException('Proveedor caído'));

        $attempt->refresh();
        $this->assertSame('started', $attempt->status);
        $this->assertCount(2 + count($attempt->analysis['skills']), $attempt->questions);
    }

    public function test_mentor_questionnaire_is_generated_in_the_background(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('mentor-application.questionnaire'), ['professional_title' => 'Excel', 'specialties' => 'Tablas dinámicas'])
            ->assertRedirect(route('mentor-application.create'));

        Queue::assertPushedOn('ai', GenerateMentorQuestionnaireJob::class);
        $this->actingAs($user)->get(route('mentor-application.create'))->assertOk()->assertSee('Estamos preparando tu cuestionario');

        (new GenerateMentorQuestionnaireJob($user->id, 'Excel', 'Tablas dinámicas'))->failed(new RuntimeException('Proveedor caído'));

        $this->assertSame('ready', MentorQuestionnaireDraft::get($user->id)['status']);
        $this->actingAs($user)->get(route('mentor-application.create'))->assertOk()
            ->assertDontSee('Estamos preparando tu cuestionario')
            ->assertSee('answers[answer_20]', false)
            ->assertSee('value="Tablas dinámicas"', false);
    }
}
