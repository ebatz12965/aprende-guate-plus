<?php

namespace Tests\Feature;

use App\Jobs\GenerateDiagnosticQuestionsJob;
use App\Jobs\GenerateLearningPathJob;
use App\Models\DiagnosticAttempt;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\Learning\DiagnosticQuestionService;
use App\Services\Learning\DiagnosticScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LevelDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private const SKILLS = ['Fórmulas básicas', 'Funciones condicionales', 'Tablas dinámicas', 'Gráficos', 'Validación de datos'];

    public function test_the_goal_is_analyzed_and_a_graded_questionnaire_is_built_from_it(): void
    {
        [$user, $attempt] = $this->attempt('preparing');
        Http::fakeSequence()->push($this->ai($this->analysis()))->push($this->ai($this->analysis()))->push($this->ai($this->questions()))->push($this->ai($this->questions()));

        app()->call([new GenerateDiagnosticQuestionsJob($attempt->id), 'handle']);

        $attempt->refresh();
        $this->assertSame('Auxiliar contable', $attempt->analysis['target_role']);
        $knowledge = collect($attempt->questions)->where('kind', 'knowledge');
        $this->assertCount(10, $knowledge);
        $this->assertSame(self::SKILLS, $knowledge->pluck('skill')->unique()->values()->all());
        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Funciones condicionales') && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'diagnóstico'));

        $this->actingAs($user)->get(route('adaptive.diagnostic.questions', $attempt))->assertOk()
            ->assertSee('Esto entendimos de tu meta')
            ->assertSee('Quieres trabajar como auxiliar contable usando Excel.')
            ->assertSee('Tablas dinámicas')
            ->assertSee('No lo sé')
            ->assertDontSee('EXPLICACIÓN SECRETA');
    }

    public function test_answers_are_graded_by_the_system_and_the_level_is_shown(): void
    {
        [$user, $attempt] = $this->attempt('started');
        $attempt->update(['analysis' => $this->analysis(), 'questions' => array_merge(app(DiagnosticQuestionService::class)->contextQuestions(), $this->storedQuestions())]);
        Queue::fake();

        // Contexto: 2 respuestas. Conocimiento: correctas las básicas de las dos primeras habilidades, «No lo sé» en el resto.
        $answers = [1, 2];
        foreach ($this->storedQuestions() as $index => $question) {
            $answers[] = $index < 4 && $question['difficulty'] === 1 ? $question['answer'] : -1;
        }
        $this->actingAs($user)->post(route('adaptive.diagnostic.store', $attempt), ['answers' => $answers])->assertRedirect(route('adaptive.diagnostic.generation', $attempt));

        $attempt->refresh();
        $results = collect($attempt->skill_results['assessment']['skills'])->keyBy('name');
        $this->assertSame(33, $results['Fórmulas básicas']['score']);
        $this->assertSame('Básico', $results['Fórmulas básicas']['level']);
        $this->assertSame(0, $results['Tablas dinámicas']['score']);
        $this->assertSame('De 2 a 4 horas', $attempt->skill_results['assessment']['context']['weekly_time']);
        Queue::assertPushed(GenerateLearningPathJob::class);

        $this->actingAs($user)->get(route('adaptive.diagnostic.generation', $attempt))->assertOk()
            ->assertSee('Tu nivel de partida: '.$attempt->level)
            ->assertSee('1 de 2 correctas');
    }

    public function test_answers_out_of_range_are_rejected(): void
    {
        [$user, $attempt] = $this->attempt('started');
        $attempt->update(['analysis' => $this->analysis(), 'questions' => array_merge(app(DiagnosticQuestionService::class)->contextQuestions(), $this->storedQuestions())]);

        // «No lo sé» no existe en las preguntas de contexto.
        $answers = array_merge([-1, 0], array_fill(0, 10, 0));
        $this->actingAs($user)->post(route('adaptive.diagnostic.store', $attempt), ['answers' => $answers])->assertSessionHasErrors('answers.0');
    }

    public function test_the_path_is_designed_from_the_measured_gaps_with_a_real_quiz_per_phase(): void
    {
        [$user, $attempt] = $this->attempt('generating');
        $assessment = app(DiagnosticScorer::class)->score($this->storedQuestions(), array_fill(0, 10, -1), $this->analysis());
        $attempt->update(['analysis' => $this->analysis(), 'questions' => $this->storedQuestions(), 'answers' => array_fill(0, 10, -1), 'skill_results' => ['assessment' => $assessment], 'level' => $assessment['level']]);
        Http::fakeSequence()->push($this->ai($this->architecture()))->push($this->ai($this->architecture()));

        app()->call([new GenerateLearningPathJob($attempt->id), 'handle']);

        Http::assertSent(fn (Request $request) => str_contains((string) data_get($request->data(), 'messages.1.content'), '"measured_level":"Inicial"'));
        $path = LearningPath::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Inicial', $path->estimated_level);
        $this->assertSame('¿Qué fórmula suma un rango?', $path->items()->first()->quiz['question']);
        $this->assertSame(0, $attempt->fresh()->skill_results['assessment']['score']);
    }

    public function test_scorer_weights_difficulty_and_maps_levels(): void
    {
        $questions = [
            ['kind' => 'knowledge', 'skill' => 'A', 'difficulty' => 1, 'answer' => 0, 'options' => ['a', 'b', 'c', 'd']],
            ['kind' => 'knowledge', 'skill' => 'A', 'difficulty' => 3, 'answer' => 2, 'options' => ['a', 'b', 'c', 'd']],
            ['kind' => 'self', 'skill' => 'B', 'difficulty' => 1, 'options' => ['0', '1', '2', '3']],
        ];

        $result = app(DiagnosticScorer::class)->score($questions, [1, 2, 3], ['skills' => [['name' => 'A', 'importance' => 'alta'], ['name' => 'B', 'importance' => 'media']]]);

        $this->assertSame([75, 100], array_column($result['skills'], 'score'));
        $this->assertSame(['Avanzado', 'Avanzado'], array_column($result['skills'], 'level'));
        $this->assertSame(83, $result['score']);
        $this->assertSame('Inicial', app(DiagnosticScorer::class)->level(10));
    }

    private function attempt(string $status): array
    {
        $user = User::factory()->create();

        return [$user, DiagnosticAttempt::create(['user_id' => $user->id, 'primary_answer' => 'Quiero ser auxiliar contable y usar bien Excel', 'answers' => [], 'status' => $status])];
    }

    private function ai(array $payload): array
    {
        return ['choices' => [['message' => ['content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]]]];
    }

    private function analysis(): array
    {
        return [
            'summary' => 'Quieres trabajar como auxiliar contable usando Excel.', 'target_role' => 'Auxiliar contable', 'motivation' => 'empleo', 'domain' => 'contabilidad',
            'skills' => array_map(fn ($name, $index) => ['name' => $name, 'why' => 'La pide el puesto.', 'importance' => $index < 2 ? 'alta' : 'media', 'subskills' => ['Una', 'Dos', 'Tres']], self::SKILLS, array_keys(self::SKILLS)),
            'prior_knowledge_signals' => [], 'constraints' => [], 'ambiguities' => [], 'assumptions' => [], 'recommended_practice_types' => ['excel_dataset'], 'market_context' => 'Se usa en empresas.',
        ];
    }

    private function questions(): array
    {
        return ['questions' => array_map(fn ($question) => collect($question)->except('kind')->all(), $this->storedQuestions())];
    }

    private function storedQuestions(): array
    {
        $questions = [];
        foreach (self::SKILLS as $index => $skill) {
            foreach ([1, 2] as $difficulty) {
                $questions[] = ['kind' => 'knowledge', 'skill' => $skill, 'difficulty' => $difficulty, 'question' => "¿Pregunta {$difficulty} de {$skill}?", 'options' => ["A {$skill} {$difficulty}", "B {$skill} {$difficulty}", "C {$skill} {$difficulty}", "D {$skill} {$difficulty}"], 'answer' => ($index + $difficulty) % 4, 'explanation' => 'EXPLICACIÓN SECRETA'];
            }
        }

        return $questions;
    }

    private function architecture(): array
    {
        $module = fn (int $index) => [
            'title' => 'Fase '.$index, 'description' => 'Descripción', 'skill' => self::SKILLS[$index % 5], 'skill_type' => 'ofimatica',
            'topics' => array_map(fn ($topic) => ['title' => "Tema {$topic}", 'learning_objective' => 'Calcular el total vendido por categoría en un reporte semanal.', 'practice_type' => 'excel_dataset'], [1, 2, 3]),
            'quiz' => ['question' => $index === 0 ? '¿Qué fórmula suma un rango?' : '¿Pregunta de la fase '.$index.'?', 'options' => ['SUMA', 'CONTAR', 'PROMEDIO'], 'answer' => 0],
        ];

        return ['level' => 'Inicial', 'title' => 'Ruta contable', 'summary' => 'Empieza por fórmulas.', 'total_hours' => 40, 'modules' => array_map($module, [0, 1, 2, 3, 4])];
    }
}
