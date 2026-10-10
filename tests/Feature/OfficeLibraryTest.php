<?php

namespace Tests\Feature;

use App\Jobs\GenerateLibraryWalkthroughJob;
use App\Jobs\GenerateTopicContentJob;
use App\Models\AiRequest;
use App\Models\LearningPathTopic;
use App\Models\LibraryTopic;
use App\Models\User;
use App\Services\Library\ExcelFormulaEvaluator;
use App\Services\Library\LibraryContentService;
use App\Services\Library\OfficePathBuilder;
use App\Services\Library\WalkthroughService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;

class OfficeLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('library:sync')->assertSuccessful();
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        return $user;
    }

    private function firstExcelTopic(User $user, string $from = 'principiante'): LearningPathTopic
    {
        $path = app(OfficePathBuilder::class)->build($user, $from, 'experto', ['excel']);

        return LearningPathTopic::whereIn('learning_path_item_id', $path->items()->pluck('id'))->orderBy('learning_path_item_id')->orderBy('position')->skip(2)->firstOrFail();
    }

    public function test_the_syllabus_is_synced_once_without_using_ai(): void
    {
        Http::fake();
        $this->artisan('library:sync')->assertSuccessful();

        $this->assertSame(54, LibraryTopic::count());
        $this->assertSame(17, LibraryTopic::where('tool', 'excel')->count());
        $this->assertSame(['principiante', 'intermedio', 'avanzado'], LibraryTopic::where('tool', 'outlook')->distinct()->orderBy('level_order')->pluck('level')->all());
        Http::assertNothingSent();
    }

    public function test_a_beginner_gets_the_whole_course_and_an_intermediate_learner_only_what_is_missing(): void
    {
        $full = app(OfficePathBuilder::class)->build($this->student());
        $this->assertSame(15, $full->items()->count());
        $this->assertSame('Word · Principiante', $full->items()->orderBy('position')->first()->title);
        $this->assertSame(54, LearningPathTopic::whereIn('learning_path_item_id', $full->items()->pluck('id'))->count());

        $user = $this->student();
        $partial = app(OfficePathBuilder::class)->build($user, 'intermedio', 'experto', ['excel', 'word']);
        $titles = $partial->items()->orderBy('position')->pluck('title')->all();
        $this->assertSame(['Excel · Intermedio', 'Word · Intermedio', 'Excel · Avanzado', 'Word · Avanzado', 'Excel · Experto', 'Word · Experto'], $titles);
        $this->assertNotContains('SUMA, PROMEDIO, MAX, MIN y CONTAR', LearningPathTopic::whereIn('learning_path_item_id', $partial->items()->pluck('id'))->pluck('title')->all());
        $this->assertSame('Dominar Microsoft Office (Excel, Word) de nivel Intermedio a Experto', $user->studentProfile->professional_goal);
    }

    public function test_the_command_creates_the_demo_account_with_its_path(): void
    {
        $this->artisan('library:office-path', ['email' => 'office.demo@example.com', '--create' => true, '--from' => 'principiante'])
            ->expectsOutputToContain('15 fases y 54 temas')->assertSuccessful();

        $user = User::where('email', 'office.demo@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Student'));
        $this->actingAs($user)->get(route('adaptive.path'))->assertOk()->assertSee('Excel · Principiante');
    }

    public function test_a_library_lesson_is_generated_once_and_shared(): void
    {
        Bus::fake([GenerateLibraryWalkthroughJob::class]);
        Http::fakeSequence()->push($this->ai($this->lesson('Borrador')))->push($this->ai($this->lesson('Lección revisada')));
        $first = $this->firstExcelTopic($this->student());
        $second = $this->firstExcelTopic($this->student());

        app()->call([new GenerateTopicContentJob($first->id, 'lesson'), 'handle']);
        app()->call([new GenerateTopicContentJob($second->id, 'lesson'), 'handle']);

        // Borrador y revisión una sola vez; la segunda persona recibe la misma lección sin llamar a la IA.
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'biblioteca del curso'));
        $library = LibraryTopic::findOrFail($first->metadata['library_topic_id']);
        $this->assertSame($library->id, $first->fresh()->lesson->content['library_topic_id']);
        $this->assertStringStartsWith('Lección revisada', $second->fresh()->lesson->content['intro']);
        $this->assertNotNull($library->lesson_generated_at);
        Bus::assertDispatchedTimes(GenerateLibraryWalkthroughJob::class, 1);
    }

    public function test_the_generic_fallback_is_never_stored_in_the_library(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $topic = $this->firstExcelTopic($this->student());

        try {
            app()->call([new GenerateTopicContentJob($topic->id, 'lesson'), 'handle']);
            $this->fail('Se esperaba que la generación fallara.');
        } catch (Throwable) {
        }

        $this->assertNull(LibraryTopic::findOrFail($topic->metadata['library_topic_id'])->lesson);
        $this->assertSame('failed', $topic->fresh()->lesson_generation_status);
    }

    public function test_the_walkthrough_computes_excel_results_and_is_narrated_once(): void
    {
        Storage::fake('local');
        config(['services.elevenlabs.key' => 'el-test', 'services.elevenlabs.voice_id' => 'voz123']);
        $wrong = $this->walkthrough('999');
        $ai = [$this->ai($wrong), $this->ai($wrong + ['review_notes' => []])];
        Http::fake(fn (Request $request) => str_contains($request->url(), 'api.elevenlabs.io') ? Http::response('ID3-audio', 200, ['Content-Type' => 'audio/mpeg']) : Http::response(array_shift($ai)));
        $library = LibraryTopic::where('slug', 'like', 'office.excel.principiante.suma%')->firstOrFail();
        $library->update(['lesson' => $this->lesson('Base')]);

        GenerateLibraryWalkthroughJob::dispatchSync($library->id);
        $walkthrough = $library->fresh()->walkthrough;

        // El modelo dijo 999; el sistema calcula 100 + 200 = 300 y copia la fórmula hacia abajo.
        $this->assertSame(['D2' => '300'], $walkthrough['steps'][1]['results']);
        $this->assertTrue($walkthrough['steps'][1]['verified']);
        $this->assertSame(['D2' => '300', 'D3' => '200', 'D4' => '400'], $walkthrough['steps'][2]['results']);
        $this->assertSame('900', $walkthrough['steps'][4]['result']);
        $this->assertTrue($walkthrough['steps'][0]['audio']);
        $this->assertSame('elevenlabs', $walkthrough['narration']['provider']);
        Storage::disk('local')->assertExists("library/narration/{$library->id}/1.mp3");
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'text-to-speech/voz123') && $request->hasHeader('xi-api-key', 'el-test') && $request['text'] === $wrong['steps'][0]['narration']);
        $this->assertSame('completed', AiRequest::where('provider', 'elevenlabs')->sole()->status);

        // Ya existe: no se vuelve a generar ni a narrar.
        GenerateLibraryWalkthroughJob::dispatchSync($library->id);
        Http::assertSentCount(2 + 6);

        $this->actingAs($this->student())->get(route('library.narration', [$library, 1]))->assertOk()->assertHeader('Content-Type', 'audio/mpeg');
        $this->get(route('library.narration', [$library, 9]))->assertNotFound();
    }

    public function test_only_the_steps_without_audio_are_narrated_again(): void
    {
        Storage::fake('local');
        config(['services.elevenlabs.key' => 'el-test']);
        Http::fake(['*' => Http::response('ID3-audio', 200, ['Content-Type' => 'audio/mpeg'])]);
        $walkthrough = app(WalkthroughService::class)->normalize($this->walkthrough('300'), 'excel');
        foreach ($walkthrough['steps'] as $index => &$step) {
            $step['audio'] = ! in_array($index, [1, 4], true);
        }
        unset($step);
        $library = LibraryTopic::where('slug', 'like', 'office.excel.principiante.suma%')->firstOrFail();
        $library->update(['walkthrough' => $walkthrough + ['narration' => ['characters' => 100]]]);

        $this->artisan('library:generate', ['slugs' => ['excel.principiante.suma'], '--narration' => true])->expectsOutputToContain('pasos narrados: 2')->assertSuccessful();

        Http::assertSentCount(2);
        $this->assertTrue(collect($library->fresh()->walkthrough['steps'])->every(fn ($step) => $step['audio']));
        Storage::disk('local')->assertExists("library/narration/{$library->id}/5.mp3");
        Storage::disk('local')->assertMissing("library/narration/{$library->id}/1.mp3");
    }

    public function test_without_an_elevenlabs_key_the_walkthrough_uses_captions(): void
    {
        config(['services.elevenlabs.key' => null]);
        Http::fake(['*' => Http::sequence()->push($this->ai($this->walkthrough('300')))->push($this->ai($this->walkthrough('300') + ['review_notes' => []]))]);
        $library = LibraryTopic::where('slug', 'like', 'office.excel.principiante.suma%')->firstOrFail();
        $library->update(['lesson' => $this->lesson('Base')]);

        GenerateLibraryWalkthroughJob::dispatchSync($library->id);

        $this->assertNull($library->fresh()->walkthrough['narration']);
        $this->assertFalse($library->fresh()->walkthrough['steps'][0]['audio']);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'elevenlabs'));
    }

    public function test_the_lesson_shows_the_animated_walkthrough(): void
    {
        Bus::fake([GenerateLibraryWalkthroughJob::class]);
        $user = $this->student();
        $topic = $this->firstExcelTopic($user);
        $library = LibraryTopic::findOrFail($topic->metadata['library_topic_id']);
        $library->update(['lesson' => $this->lesson('Compartida'), 'lesson_generated_at' => now(), 'walkthrough' => app(WalkthroughService::class)->normalize($this->walkthrough('300'), 'excel')]);
        app(LibraryContentService::class)->lesson($library);
        app()->call([new GenerateTopicContentJob($topic->id, 'lesson'), 'handle']);

        $this->actingAs($user)->get(route('adaptive.topic.show', $topic))->assertOk()
            ->assertSee('Míralo en acción')->assertSee('officeWalkthrough(', false)->assertSee('Disposición de página');
    }

    public function test_formulas_are_evaluated_and_shifted_like_excel(): void
    {
        $excel = new ExcelFormulaEvaluator;
        $cells = ['B2' => '150', 'B3' => 'Q 1,250.50', 'B4' => '99.5', 'C2' => '12%', 'A1' => 'Producto'];

        $this->assertSame(1500.0, $excel->evaluate('=SUMA(B2:B4)', $cells));
        $this->assertSame(168.0, $excel->evaluate('=B2*(1+C2)', $cells));
        $this->assertSame(416.83, $excel->evaluate('=REDONDEAR(B3/3;2)', $cells));
        $this->assertSame(4.0, $excel->evaluate('=CONTARA(A1:B4)', $cells));
        $this->assertNull($excel->evaluate('=SI(B2>100,"Sí","No")', $cells));
        $this->assertNull($excel->evaluate('=A1*2', $cells));

        $shift = app(WalkthroughService::class)->shift(...);
        $this->assertSame('=B3*$F$1+C$2', $shift('=B2*$F$1+C$2', 1, 0));
        $this->assertSame('=SUMA(C2:D2)', $shift('=SUMA(B2:C2)', 0, 1));
    }

    private function ai(array $payload): array
    {
        return ['choices' => [['message' => ['content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]]], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10]];
    }

    private function walkthrough(string $modelResult): array
    {
        $step = fn (string $action, string $tab, string $control, string $target, string $value, string $result) => compact('action', 'tab', 'control', 'target', 'value', 'result')
            + ['narration' => "Ahora {$action} en {$target} para avanzar con el reporte de ventas de la ferretería."];

        return [
            'file_name' => 'ventas.xlsx', 'intro' => 'Verás cómo sumar las ventas de cada persona.',
            'sheet' => ['rows' => [['Vendedor', 'Ene', 'Feb', 'Total'], ['Ana', '100', '200', ''], ['Luis', '150', '50', ''], ['Marta', '300', '100', ''], ['Total', '', '', '']]],
            'document' => ['title' => '', 'meta' => '', 'paragraphs' => []],
            'steps' => [
                $step('tab', 'Inicio', '', '', '', ''),
                $step('type', '', '', 'D2', '=SUMA(B2:C2)', $modelResult),
                $step('fill', '', '', 'D2:D4', '=SUMA(B2:C2)', ''),
                $step('click', 'Inicio', 'Autosuma', 'D5', '', ''),
                $step('type', '', '', 'D5', '=SUMA(D2:D4)', '1000'),
                $step('format', 'Inicio', 'Formato de número de contabilidad', 'D2:D5', 'moneda', ''),
            ],
            'summary' => 'Obtuviste el total de cada persona y el total general.',
        ];
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
            'worked_example' => ['scenario' => 'Un reporte quincenal.', 'material' => "Vendedor,Total\nAna,450\nLuis,780", 'steps' => [['action' => 'Ordena', 'explanation' => 'Para agrupar.'], ['action' => 'Suma', 'explanation' => 'Para obtener el total.'], ['action' => 'Revisa', 'explanation' => 'Para detectar errores.']], 'result' => 'Ana vendió Q 450.'],
            'common_mistakes' => [['mistake' => 'No revisar.', 'fix' => 'Compara con otro método.'], ['mistake' => 'Rangos distintos.', 'fix' => 'Usa rangos del mismo tamaño.']],
            'key_concepts' => [['term' => 'Rango', 'definition' => 'Un grupo de celdas.'], ['term' => 'Criterio', 'definition' => 'La condición a cumplir.'], ['term' => 'Total', 'definition' => 'La suma.']],
            'check_questions' => [$question(0), $question(2), $question(1)],
            'decision' => ['situation' => 'Tu jefa necesita el reporte en una hora y un total no cuadra.', 'question' => '¿Qué harías?', 'choices' => [['label' => 'Entregarlo así', 'best' => false, 'outcome' => 'Entregas un error.'], ['label' => 'Revisar la fórmula', 'best' => true, 'outcome' => 'Encuentras el error a tiempo.']]],
            'summary' => 'Agrupa, suma y revisa.',
            'youtube_queries' => ['SUMA en Excel'],
        ];
    }
}
