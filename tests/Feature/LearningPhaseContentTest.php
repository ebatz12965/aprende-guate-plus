<?php

namespace Tests\Feature;

use App\Jobs\GenerateMilestoneChallengeJob;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LearningPhaseContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_formula_phase_remains_accessible_after_the_pipeline_update(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Excel', 'goal' => 'Aprender Excel', 'status' => 'active']);
        $item = $path->items()->create([
            'title' => 'Fórmulas básicas', 'area' => 'Fórmulas', 'description' => 'Aprende a sumar, restar, multiplicar y dividir.',
            'content' => ['concept' => 'Las fórmulas realizan cálculos en Excel.'],
            'quiz' => ['question' => '¿Cuál fórmula suma?', 'options' => ['=A1+B1', '=A1-B1', '=A1/B1'], 'answer' => 0],
        ]);

        $this->actingAs($user)->get(route('adaptive.path.item.show', $item))->assertOk()
            ->assertSee('Fórmulas básicas')->assertSee('Esta ruta fue creada antes del nuevo pipeline')
            ->assertDontSee('PRACTICA AHORA');
    }

    public function test_new_module_lists_its_persisted_topics(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Computación', 'goal' => 'Conocer computación', 'status' => 'active']);
        $item = $path->items()->create(['title' => 'Componentes básicos', 'area' => 'Computación', 'description' => 'Conoce las partes de una computadora.', 'quiz' => ['question' => 'Pregunta', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
        $item->topics()->create(['title' => 'Procesador', 'learning_objective' => 'Reconocer la función de la CPU.', 'practice_type' => 'theory_quiz', 'position' => 1]);

        $this->actingAs($user)->get(route('adaptive.path.item.show', $item))->assertOk()
            ->assertSee('Procesador')->assertSee('Reconocer la función de la CPU.')
            ->assertSee('genera una lección y una práctica solo cuando la solicitas');
    }

    public function test_quiz_attempts_are_recorded_and_only_correct_answers_complete_the_phase(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => 'Meta', 'status' => 'active']);
        $item = $path->items()->create([
            'title' => 'Fase inicial', 'area' => 'Base', 'description' => 'Concepto inicial',
            'content' => ['concept' => 'Concepto inicial'],
            'quiz' => ['question' => '¿Cuál es correcta?', 'options' => ['Correcta', 'Incorrecta', 'Otra'], 'answer' => 0],
            'position' => 1,
        ]);

        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $item), ['answer' => 1])
            ->assertSessionHas('quiz_error');

        $this->assertDatabaseHas('learning_path_item_quiz_attempts', [
            'learning_path_item_id' => $item->id,
            'user_id' => $user->id,
            'selected_answer' => 1,
            'is_correct' => false,
        ]);
        $this->assertDatabaseHas('learning_path_items', ['id' => $item->id, 'status' => 'pending']);

        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $item), ['answer' => '0'])
            ->assertRedirect(route('adaptive.career'));

        $this->assertDatabaseHas('learning_path_item_quiz_attempts', [
            'learning_path_item_id' => $item->id,
            'user_id' => $user->id,
            'selected_answer' => 0,
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('learning_path_items', ['id' => $item->id, 'status' => 'completed']);
        $this->assertDatabaseHas('learning_paths', ['id' => $path->id, 'status' => 'completed']);
    }

    public function test_completing_a_quarter_of_the_route_queues_only_the_matching_optional_challenge(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta de Excel', 'goal' => 'Aprender Excel', 'status' => 'active']);
        foreach (range(1, 4) as $position) {
            $path->items()->create(['title' => 'Fase '.$position, 'area' => 'Excel', 'description' => 'Contenido', 'position' => $position, 'quiz' => ['question' => 'Pregunta', 'options' => ['Correcta', 'No', 'No'], 'answer' => 0]]);
        }
        $first = $path->items()->where('position', 1)->firstOrFail();

        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $first), ['answer' => 0])
            ->assertRedirect(route('adaptive.path'));

        $this->assertDatabaseHas('challenges', ['user_id' => $user->id, 'learning_path_id' => $path->id, 'milestone' => 25, 'generation_status' => 'queued']);
        $this->assertDatabaseMissing('challenges', ['user_id' => $user->id, 'learning_path_id' => $path->id, 'milestone' => 50]);
        Queue::assertPushed(GenerateMilestoneChallengeJob::class, fn ($job) => $job->learningPathId === $path->id && $job->milestone === 25);
    }

    public function test_formula_quiz_accepts_the_matching_option_sent_by_the_browser(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Excel', 'goal' => 'Aprender Excel', 'status' => 'active']);
        $item = $path->items()->create([
            'title' => 'Fórmulas básicas',
            'area' => 'Fórmulas',
            'description' => 'Aprende fórmulas.',
            'content' => ['concept' => 'Usa Excel para cálculos.'],
            'quiz' => ['question' => '¿Qué hace la fórmula =A1+B1?', 'options' => ['Resta A1 de B1', 'Suma A1 y B1', 'Multiplica A1 por B1'], 'answer' => 0],
            'position' => 1,
        ]);

        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $item), ['answer' => '1'])
            ->assertRedirect(route('adaptive.career'));

        $this->assertSame(1, $item->fresh()->quiz['answer']);
        $this->assertDatabaseHas('learning_path_item_quiz_attempts', [
            'learning_path_item_id' => $item->id,
            'selected_answer' => 1,
            'is_correct' => true,
        ]);
    }
}
