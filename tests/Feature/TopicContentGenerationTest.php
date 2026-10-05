<?php

namespace Tests\Feature;

use App\Jobs\EvaluatePracticeAttemptJob;
use App\Jobs\GenerateTopicContentJob;
use App\Models\LearningPath;
use App\Models\LearningPathPracticeAttempt;
use App\Models\LearningPathTopic;
use App\Models\User;
use App\Services\Learning\TopicContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TopicContentGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_is_drafted_reviewed_and_rendered(): void
    {
        [$user, $topic] = $this->topic('code_complete');
        $reviewed = $this->lesson('Versión revisada');
        Http::fakeSequence()->push($this->ai($this->classification()))->push($this->ai($this->lesson('Borrador')))->push($this->ai($reviewed));

        app()->call([new GenerateTopicContentJob($topic->id, 'lesson'), 'handle']);

        $content = $topic->fresh()->lesson->content;
        $this->assertSame(2, $content['version']);
        $this->assertStringStartsWith('Versión revisada', $content['intro']);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Borrador a revisar'));

        $this->actingAs($user)->get(route('adaptive.topic.show', $topic))->assertOk()
            ->assertSee('Versión revisada')
            ->assertSee('Ejemplo resuelto')
            ->assertSee('Errores frecuentes')
            ->assertSee('Comprueba lo que aprendiste')
            ->assertSee('Elige por dónde empezar')
            ->assertSee('¿Qué harías?')
            ->assertSee('Ver como texto');
    }

    public function test_an_incomplete_draft_is_retried_with_its_problems(): void
    {
        [, $topic] = $this->topic('code_complete');
        $short = $this->lesson('Corta');
        $short['sections'] = array_slice($short['sections'], 0, 1);
        Http::fakeSequence()->push($this->ai($this->classification()))->push($this->ai($short))->push($this->ai($this->lesson('Reintento')))->push($this->ai($this->lesson('Revisada')));

        app()->call([new GenerateTopicContentJob($topic->id, 'lesson'), 'handle']);

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Debe haber al menos 3 secciones'));
        $this->assertStringStartsWith('Revisada', $topic->fresh()->lesson->content['intro']);
    }

    public function test_brief_sections_go_to_the_reviewer_instead_of_the_generic_fallback(): void
    {
        [, $topic] = $this->topic('code_complete');
        $brief = $this->lesson('Breve');
        $brief['sections'] = array_map(fn ($section) => ['explanation' => 'Muy corta.'] + $section, $brief['sections']);
        Http::fakeSequence()->push($this->ai($this->classification()))->push($this->ai($brief))->push($this->ai($brief))->push($this->ai($this->lesson('Ampliada')));

        app()->call([new GenerateTopicContentJob($topic->id, 'lesson'), 'handle']);

        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Además corrige estos problemas'));
        $this->assertStringStartsWith('Ampliada', $topic->fresh()->lesson->content['intro']);
    }

    public function test_practice_shows_data_criteria_and_hints_but_never_the_reference_solution(): void
    {
        [$user, $topic] = $this->topic('excel_dataset');
        // La revisión devuelve algo inválido: se conserva el borrador ya validado.
        Http::fakeSequence()->push($this->ai($this->classification()))->push($this->ai($this->practice()))->push($this->ai(['title' => 'roto']));

        app()->call([new GenerateTopicContentJob($topic->id, 'practice'), 'handle']);

        $practice = $topic->fresh()->practice;
        $this->assertSame('table', $practice->artifact['kind']);
        $this->assertStringStartsWith('SOLUCIÓN SECRETA: Ana vendió 1680.', $practice->expected_solution['reference']);

        $this->actingAs($user)->get(route('adaptive.topic.show', $topic))->assertOk()
            ->assertSee('Datos de la práctica')
            ->assertSee('<th scope="col" class="whitespace-nowrap px-3 py-2 font-bold text-slate-900">Vendedor</th>', false)
            ->assertSee('Cómo se evaluará')
            ->assertSee('Los totales por vendedor son correctos.')
            ->assertSee('Ver la primera pista')
            ->assertDontSee('SOLUCIÓN SECRETA');
    }

    public function test_feedback_reviews_each_criterion_and_compares_with_the_previous_attempt(): void
    {
        [$user, $topic] = $this->topic('excel_dataset');
        $evaluation = ['score' => 85, 'summary' => 'Buen avance.', 'criteria' => [['criterion' => 'Los totales por vendedor son correctos.', 'met' => true, 'comment' => 'Los tres totales coinciden.']], 'strengths' => ['Usaste SUMAR.SI.'], 'improvements' => ['Agrega la conclusión.'], 'progress' => 'Ahora calculaste el total de Luis, que faltaba antes.'];
        Http::fakeSequence()->push($this->ai($this->classification()))->push($this->ai($this->practice()))->push($this->ai($this->practice()))->push($this->ai($evaluation));
        app()->call([new GenerateTopicContentJob($topic->id, 'practice'), 'handle']);
        $practice = $topic->fresh()->practice;
        LearningPathPracticeAttempt::create(['learning_path_practice_id' => $practice->id, 'user_id' => $user->id, 'answer' => 'Primer intento', 'status' => 'evaluated', 'score' => 50, 'evaluation' => ['improvements' => ['Calcula el total de Luis.']], 'submitted_at' => now()]);
        $second = LearningPathPracticeAttempt::create(['learning_path_practice_id' => $practice->id, 'user_id' => $user->id, 'answer' => 'Segundo intento con =SUMAR.SI', 'status' => 'queued', 'submitted_at' => now()]);

        app()->call([new EvaluatePracticeAttemptJob($second->id), 'handle']);

        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Calcula el total de Luis'));
        $this->actingAs($user)->get(route('adaptive.topic.show', $topic))->assertOk()
            ->assertSee('Criterio por criterio')
            ->assertSee('Los tres totales coinciden.')
            ->assertSee('Ahora calculaste el total de Luis, que faltaba antes.')
            ->assertSee('Intento 1:')
            ->assertSee('Enviar nuevo intento')
            ->assertSee('role="tablist"', false)
            ->assertSee('aria-controls="panel-practica"', false)
            ->assertSee('85/100');
    }

    public function test_numeric_results_are_computed_from_the_csv_not_trusted_from_the_model(): void
    {
        $csv = "Fecha,Categoría,Monto\n2026-01-01,Electrónica,1500\n2026-01-02,Electrónica,2000\n2026-01-03,Hogar,800\n2026-01-04,Electrónica,1200\n2026-01-05,Hogar,600";
        $results = app(TopicContentService::class)->computeChecks($csv, [
            ['label' => 'Total Electrónica', 'operation' => 'suma', 'value_column' => 'Monto', 'filter_column' => 'Categoria', 'filter_value' => 'electronica'],
            ['label' => 'Ventas de Hogar', 'operation' => 'conteo', 'value_column' => '', 'filter_column' => 'Categoría', 'filter_value' => 'Hogar'],
            ['label' => 'Promedio', 'operation' => 'promedio', 'value_column' => 'Monto', 'filter_column' => '', 'filter_value' => ''],
            ['label' => 'Columna inexistente', 'operation' => 'suma', 'value_column' => 'Precio', 'filter_column' => '', 'filter_value' => ''],
        ]);

        $this->assertSame(['4700', '2', '1220', null], array_column($results, 'value'));
        $this->assertNotNull($results[3]['error']);
    }

    public function test_a_practice_whose_checks_reference_missing_columns_is_retried(): void
    {
        [, $topic] = $this->topic('excel_dataset');
        $broken = $this->practice();
        $broken['checks'] = [['label' => 'Total', 'operation' => 'suma', 'value_column' => 'Precio', 'filter_column' => '', 'filter_value' => '']];
        Http::fakeSequence()->push($this->ai($this->classification()))->push($this->ai($broken))->push($this->ai($this->practice()))->push($this->ai($this->practice()));

        app()->call([new GenerateTopicContentJob($topic->id, 'practice'), 'handle']);

        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'no existe la columna numérica'));
        $practice = $topic->fresh()->practice;
        $this->assertSame([['label' => 'Total de Ana', 'value' => '5500']], $practice->expected_solution['results']);
        $this->assertStringContainsString('Total de Ana = 5500', $practice->expected_solution['reference']);
    }

    private function topic(string $practiceType): array
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => 'Conseguir empleo como auxiliar contable', 'status' => 'active']);
        $module = $path->items()->create(['title' => 'Fundamentos', 'area' => 'Contabilidad', 'description' => 'Base', 'position' => 1, 'status' => 'in_progress', 'quiz' => ['question' => 'Q', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
        $topic = $module->topics()->create(['title' => 'Totales por vendedor', 'learning_objective' => 'Resumir ventas por persona.', 'practice_type' => $practiceType, 'position' => 1]);

        return [$user, LearningPathTopic::find($topic->id)];
    }

    private function ai(array $payload): array
    {
        return ['choices' => [['message' => ['content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]]], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10]];
    }

    private function classification(): array
    {
        return ['content_domain' => 'general', 'lesson_format' => 'guia_aplicada', 'practice_format' => 'case_study', 'requires_downloadable_file' => false, 'display_mode' => 'rich_instructions', 'interaction' => 'preguntas'];
    }

    private function lesson(string $marker): array
    {
        $paragraph = str_repeat('Explicamos el porqué de este paso con un caso de una empresa guatemalteca. ', 4)."\n\n".str_repeat('Así sabrás cuándo aplicarlo y cómo revisarlo antes de entregar tu trabajo. ', 3);
        $section = fn (string $heading) => ['heading' => $heading, 'explanation' => $paragraph, 'example' => 'En una ferretería de Mixco, la gerente necesita el total vendido por cada persona durante la primera quincena.'];
        $question = fn (int $answer) => ['question' => '¿Qué haces primero?', 'options' => ['Opción uno', 'Opción dos', 'Opción tres', 'Opción cuatro'], 'answer' => $answer, 'explanation' => 'Es la correcta porque permite revisar el resultado antes de entregarlo.'];

        return [
            'intro' => $marker.': para qué sirve este tema en un empleo.',
            'objectives' => ['Calcular totales por persona.', 'Revisar el resultado.'],
            'sections' => [$section('Qué es'), $section('Cómo se hace'), $section('Cómo revisarlo')],
            'worked_example' => ['scenario' => 'Un reporte quincenal.', 'material' => "Vendedor,Total\nAna,450\nLuis,780\nAna,320\nMarta,510\nAna,910", 'steps' => [['action' => 'Ordena', 'explanation' => 'Para agrupar.'], ['action' => 'Suma', 'explanation' => 'Para obtener el total.'], ['action' => 'Revisa', 'explanation' => 'Para detectar errores.']], 'result' => 'Ana vendió Q 1,680.'],
            'common_mistakes' => [['mistake' => 'No revisar.', 'fix' => 'Compara con otro método.'], ['mistake' => 'Rangos distintos.', 'fix' => 'Usa rangos del mismo tamaño.']],
            'key_concepts' => [['term' => 'Rango', 'definition' => 'Un grupo de celdas.'], ['term' => 'Criterio', 'definition' => 'La condición a cumplir.'], ['term' => 'Total', 'definition' => 'La suma.']],
            'check_questions' => [$question(0), $question(2), $question(1)],
            'decision' => ['situation' => 'Tu jefa necesita el reporte en una hora y un total no cuadra.', 'question' => '¿Qué harías?', 'choices' => [['label' => 'Entregarlo así', 'best' => false, 'outcome' => 'Entregas un error.'], ['label' => 'Revisar la fórmula', 'best' => true, 'outcome' => 'Encuentras el error a tiempo.']]],
            'summary' => 'Agrupa, suma y revisa.',
            'youtube_queries' => ['SUMAR.SI en Excel'],
        ];
    }

    private function practice(): array
    {
        $rows = collect(range(1, 10))->map(fn ($i) => "V{$i},2026-01-0".($i % 9 + 1).',Ana,Oficina,'.($i * 100))->implode("\n");

        return [
            'title' => 'Reporte de ventas', 'context' => 'Eres auxiliar en una tienda.', 'objective' => 'Resumir ventas.',
            'instructions' => ['Abre el CSV.', 'Calcula totales.', 'Escribe una conclusión.'],
            'deliverable' => 'Pega tus fórmulas y resultados.',
            'success_criteria' => ['Los totales por vendedor son correctos.', 'Usa SUMAR.SI o tabla dinámica.', 'La conclusión se apoya en los datos.'],
            'hints' => ['Agrupa por vendedor.', 'Usa SUMAR.SI.', 'El rango de suma es la columna Total.'],
            'self_check' => ['¿Respondí todo?', '¿Revisé un total?'],
            'material' => ['label' => 'ventas.csv', 'language' => 'csv', 'content' => "ID,Fecha,Vendedor,Categoria,Total\n".$rows],
            'reference_solution' => 'SOLUCIÓN SECRETA: Ana vendió 1680.'.str_repeat(' Detalle de la solución esperada.', 3),
            'checks' => [['label' => 'Total de Ana', 'operation' => 'suma', 'value_column' => 'Total', 'filter_column' => 'Vendedor', 'filter_value' => 'Ana']],
        ];
    }
}
