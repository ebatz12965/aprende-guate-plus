<?php

namespace Tests\Feature;

use App\Jobs\GenerateInterviewPlanJob;
use App\Models\AiRequest;
use App\Models\AiTokenUsage;
use App\Models\InterviewSimulation;
use App\Models\InterviewStage;
use App\Models\LearningPath;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceInterviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private InterviewSimulation $simulation;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        app(Modules::class)->update($admin, 'entrevistas', Modules::ACTIVE, null, null, []);

        $this->user = User::factory()->create();
        $this->user->assignRole('Student');
        // Plan y preguntas con plantillas (agente apagado): solo se simula la llamada de voz.
        config(['ai.agents.interviewer.enabled' => false, 'ai.providers.openai.key' => 'sk-test', 'ai.agents.voice_interviewer.enabled' => true]);
        // Valores de producción, sin depender del .env local (que puede tener llamadas cortas de prueba).
        config(['ai.voice.max_seconds' => 300, 'ai.voice.questions' => 4, 'ai.voice.daily_limit' => 2, 'ai.voice.sessions_per_stage' => 2, 'ai.voice.unrestricted_emails' => []]);
        // La voz se habilita con una ruta de aprendizaje completada.
        $this->completePath($this->user);
        $this->simulation = InterviewSimulation::create(['user_id' => $this->user->id, 'job_title' => 'Técnico de soporte de TI', 'area' => 'soporte', 'seniority' => 'junior', 'status' => 'generating']);
        GenerateInterviewPlanJob::dispatchSync($this->simulation->id);
        $this->simulation->refresh();
    }

    private function completePath(User $user): void
    {
        LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta completada', 'goal' => 'Soporte técnico', 'status' => 'completed']);
    }

    private function hr(): InterviewStage
    {
        return $this->simulation->stages()->where('type', 'hr_interview')->firstOrFail();
    }

    private function startByVoice(): InterviewStage
    {
        $hr = $this->hr();
        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.start', [$this->simulation, $hr]), ['mode' => 'voice'])->assertRedirect();

        return $hr->refresh();
    }

    private function fakeSecret(): void
    {
        Http::fake(['api.openai.com/v1/realtime/client_secrets' => Http::response(['value' => 'ek_test_123', 'expires_at' => now()->addMinutes(2)->timestamp])]);
    }

    public function test_the_hr_stage_offers_chat_and_voice(): void
    {
        $this->actingAs($this->user)->get(route('adaptive.interviews.stage', [$this->simulation, $this->hr()]))
            ->assertOk()->assertSee('Por chat')->assertSee('Por voz')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(self), geolocation=(), payment=()');
        // Fuera de la entrevista, el micrófono sigue bloqueado.
        $this->actingAs($this->user)->get(route('adaptive.interviews'))->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_starting_by_voice_prepares_the_questions_without_a_chat(): void
    {
        $hr = $this->startByVoice();

        $this->assertSame('in_progress', $hr->status);
        $this->assertSame('voice', $hr->content['mode']);
        $this->assertNotEmpty($hr->content['questions']);
        $this->assertSame(0, $hr->turns()->count());
        $this->actingAs($this->user)->get(route('adaptive.interviews.stage', [$this->simulation, $hr]))->assertOk()->assertSee('Empezar la llamada')->assertSee('voiceCall({', false);
        // El chat no responde mientras la entrevista es por voz.
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.answer', [$this->simulation, $hr]), ['answer' => 'Hola'])->assertStatus(409);
    }

    public function test_the_session_returns_a_temporary_key_with_a_short_scripted_interview(): void
    {
        $this->fakeSecret();
        $hr = $this->startByVoice();

        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]))
            ->assertOk()->assertJson(['client_secret' => 'ek_test_123', 'model' => 'gpt-realtime-2.1-mini', 'max_seconds' => 300]);

        Http::assertSent(function (Request $request) use ($hr) {
            $session = $request->data()['session'] ?? [];
            $firstQuestion = $hr->content['questions'][0]['text'];
            $fifthQuestion = $hr->content['questions'][4]['text'] ?? '__ninguna__';

            return $request->hasHeader('Authorization', 'Bearer sk-test')
                && $session['model'] === 'gpt-realtime-2.1-mini'
                && $session['max_output_tokens'] === 1800
                && $session['audio']['input']['transcription']['language'] === 'es'
                && str_contains($session['instructions'], $firstQuestion)
                && ! str_contains($session['instructions'], $fifthQuestion)
                && str_contains($session['instructions'], 'Hemos terminado la entrevista.')
                && str_contains($session['instructions'], 'nunca preguntes ni comentes sobre edad')
                && str_contains($session['instructions'], 'Pide a la persona que se presente')
                && $session['audio']['input']['turn_detection']['eagerness'] === 'auto';
        });
        $this->assertSame(1, $hr->fresh()->content['voice']['sessions']);
        $this->assertSame('processing', AiRequest::where('operation', 'interview_voice')->sole()->status);
    }

    public function test_calls_are_limited_per_day_and_per_interview(): void
    {
        $this->fakeSecret();
        config(['ai.voice.daily_limit' => 1]);
        $hr = $this->startByVoice();
        $url = route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]);

        $this->actingAs($this->user)->postJson($url)->assertOk();
        $this->actingAs($this->user)->postJson($url)->assertStatus(422)->assertJsonValidationErrors('voice');

        config(['ai.voice.daily_limit' => 10, 'ai.voice.sessions_per_stage' => 1]);
        $this->actingAs($this->user)->postJson($url)->assertStatus(422)->assertJsonPath('errors.voice.0', 'Ya intentaste la llamada las veces permitidas en esta entrevista. Puedes terminarla por chat.');
    }

    public function test_voice_is_unavailable_when_the_agent_is_off_and_chat_still_works(): void
    {
        config(['ai.agents.voice_interviewer.enabled' => false]);
        $hr = $this->hr();

        $this->actingAs($this->user)->get(route('adaptive.interviews.stage', [$this->simulation, $hr]))->assertSee('La entrevista por voz no está disponible en este momento.');
        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.start', [$this->simulation, $hr]), ['mode' => 'voice'])->assertSessionHas('error');
        $this->assertSame('available', $hr->fresh()->status);

        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.start', [$this->simulation, $hr]), ['mode' => 'chat']);
        $this->assertSame('chat', $hr->fresh()->content['mode']);
        $this->assertSame(['opening', 'question'], $hr->fresh()->turns->pluck('meta.kind')->all());
    }

    public function test_the_technical_interview_can_also_be_by_voice_but_the_tests_cannot(): void
    {
        $this->fakeSecret();
        $technical = $this->simulation->stages()->where('type', 'technical_interview')->firstOrFail();
        $technical->update(['status' => 'available']);
        $this->actingAs($this->user)->get(route('adaptive.interviews.stage', [$this->simulation, $technical]))->assertSee('Por voz');

        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.start', [$this->simulation, $technical]), ['mode' => 'voice']);
        $this->assertSame('voice', $technical->fresh()->content['mode']);
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $technical]))->assertOk();
        Http::assertSent(fn (Request $request) => str_contains($request->data()['session']['instructions'] ?? '', 'Es una entrevista técnica'));

        // Las pruebas (razonamiento y práctica) no tienen modo de voz.
        $reasoning = $this->simulation->stages()->where('type', 'reasoning_test')->firstOrFail();
        $reasoning->update(['status' => 'in_progress', 'content' => ['mode' => 'voice']]);
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $reasoning]))->assertStatus(409);
    }

    public function test_hanging_up_saves_the_transcript_records_usage_and_evaluates(): void
    {
        $this->fakeSecret();
        $hr = $this->startByVoice();
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]))->assertOk();

        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.finish', [$this->simulation, $hr]), [
            'transcript' => [
                ['role' => 'interviewer', 'text' => 'Hola, soy Andrea. ¿Por qué te interesa este puesto?'],
                ['role' => 'candidate', 'text' => 'Me gusta resolver problemas de los usuarios.'],
                ['role' => 'interviewer', 'text' => '¿Me cuentas de un reto reciente?'],
                ['role' => 'candidate', 'text' => 'Reinstalé la red de una oficina en un fin de semana.'],
                ['role' => 'interviewer', 'text' => 'Gracias. Hemos terminado la entrevista.'],
                ['role' => 'candidate', 'text' => ''],
            ],
            'usage' => ['input_tokens' => 4200, 'output_tokens' => 2100],
            'seconds' => 190,
        ])->assertOk()->assertJsonPath('redirect', route('adaptive.interviews.stage', [$this->simulation, $hr]));

        $hr->refresh();
        $this->assertContains($hr->status, ['submitted', 'evaluated']);
        $this->assertSame(['question', 'answer', 'question', 'answer', 'closing'], $hr->turns->pluck('meta.kind')->all());
        $this->assertTrue($hr->turns->first()->meta['voice']);
        $this->assertSame(190, $hr->content['voice']['seconds']);
        $this->assertSame('available', $this->simulation->stages()->where('position', 2)->first()->status);

        $usage = AiTokenUsage::where('operation', 'interview_voice')->sole();
        $this->assertSame(6300, $usage->total_tokens);
        $this->assertSame('completed', AiRequest::where('operation', 'interview_voice')->sole()->status);
    }

    public function test_a_call_without_answers_is_not_saved_but_its_usage_is(): void
    {
        $this->fakeSecret();
        $hr = $this->startByVoice();
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]));

        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.finish', [$this->simulation, $hr]), [
            'transcript' => [['role' => 'interviewer', 'text' => 'Hola, ¿me escuchas?']], 'usage' => ['input_tokens' => 300, 'output_tokens' => 500], 'seconds' => 20,
            'diagnostics' => ['device' => 'Micrófono (Realtek)', 'max_level' => 0, 'speech_started' => 0, 'transcripts' => 0, 'responses' => 1, 'ice' => 'connected', 'errors' => ['Respuesta cortada por el límite de tokens.']],
        ])->assertStatus(422)->assertJsonValidationErrors('voice');

        // Queda el diagnóstico para saber por qué no se escuchó a la persona: el micrófono no captó sonido.
        $diagnostics = $hr->fresh()->content['voice']['diagnostics'];
        $this->assertSame(['Micrófono (Realtek)', 0, 0], [$diagnostics['device'], $diagnostics['max_level'], $diagnostics['speech_started']]);
        $this->assertSame(['Respuesta cortada por el límite de tokens.'], $diagnostics['errors']);

        $this->assertSame('in_progress', $hr->fresh()->status);
        $this->assertSame(800, (int) AiTokenUsage::where('operation', 'interview_voice')->sum('total_tokens'));
    }

    public function test_resending_the_end_of_a_call_does_not_add_usage_again(): void
    {
        $this->fakeSecret();
        $hr = $this->startByVoice();
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]))->assertOk();

        // Cada reenvío trae el tope de tokens; solo el primero cuenta para el presupuesto.
        foreach (range(1, 3) as $attempt) {
            $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.finish', [$this->simulation, $hr]), [
                'transcript' => [['role' => 'interviewer', 'text' => 'Hola, ¿me escuchas?']], 'usage' => ['input_tokens' => 900000, 'output_tokens' => 900000], 'seconds' => 3600,
            ])->assertStatus(422);
        }

        $usage = AiTokenUsage::where('operation', 'interview_voice')->sole();
        $this->assertSame(400000, $usage->total_tokens);
        $this->assertSame((config('ai.voice.max_seconds') + 60) * 1000, $usage->duration_ms);
    }

    public function test_the_interview_can_continue_by_chat(): void
    {
        $hr = $this->startByVoice();

        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.to-chat', [$this->simulation, $hr]))->assertRedirect(route('adaptive.interviews.stage', [$this->simulation, $hr]));
        $hr->refresh();
        $this->assertSame('chat', $hr->content['mode']);
        $this->assertSame(['opening', 'question'], $hr->turns->pluck('meta.kind')->all());
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]))->assertStatus(409);
    }

    public function test_voice_requires_a_completed_learning_path(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');
        $simulation = InterviewSimulation::create(['user_id' => $user->id, 'job_title' => 'Técnico de soporte de TI', 'area' => 'soporte', 'seniority' => 'junior', 'status' => 'generating']);
        GenerateInterviewPlanJob::dispatchSync($simulation->id);
        $hr = $simulation->stages()->where('type', 'hr_interview')->firstOrFail();
        // Una ruta en curso no cuenta: debe estar completada.
        LearningPath::create(['user_id' => $user->id, 'title' => 'En curso', 'goal' => 'Soporte', 'status' => 'active']);

        $this->actingAs($user)->get(route('adaptive.interviews.stage', [$simulation, $hr]))->assertSee('La entrevista por voz se habilita cuando completas una ruta de aprendizaje.');
        $this->actingAs($user)->post(route('adaptive.interviews.stage.start', [$simulation, $hr]), ['mode' => 'voice'])->assertSessionHas('error');
        $this->assertSame('available', $hr->fresh()->status);
        $this->assertSame(0, $user->fresh()->voice_interviews_used);
    }

    public function test_each_completed_path_gives_one_voice_interview(): void
    {
        $this->fakeSecret();
        $hr = $this->startByVoice();
        $this->assertSame(1, $this->user->fresh()->voice_interviews_used);
        $this->assertTrue($hr->content['voice_credit']);
        // Dentro de la misma entrevista se puede reintentar la llamada (por ejemplo, si falló el micrófono).
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]))->assertOk();

        $technical = $this->simulation->stages()->where('type', 'technical_interview')->firstOrFail();
        $technical->update(['status' => 'available']);
        $this->actingAs($this->user)->get(route('adaptive.interviews.stage', [$this->simulation, $technical]))->assertSee('Ya usaste tu entrevista por voz.');
        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.start', [$this->simulation, $technical]), ['mode' => 'voice'])->assertSessionHas('error');

        // Una segunda ruta completada habilita otra.
        $this->completePath($this->user);
        $this->actingAs($this->user)->get(route('adaptive.interviews.stage', [$this->simulation, $technical]))->assertSee('Tienes 1 entrevista por voz disponible');
        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.start', [$this->simulation, $technical]), ['mode' => 'voice']);
        $this->assertSame('voice', $technical->fresh()->content['mode']);
        $this->assertSame(2, $this->user->fresh()->voice_interviews_used);
    }

    public function test_the_voice_interview_is_returned_if_no_call_was_connected(): void
    {
        $hr = $this->startByVoice();
        $this->assertSame(1, $this->user->fresh()->voice_interviews_used);

        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.to-chat', [$this->simulation, $hr]));
        $this->assertSame(0, $this->user->fresh()->voice_interviews_used);
    }

    public function test_the_voice_interview_is_spent_once_a_call_connected(): void
    {
        $this->fakeSecret();
        $hr = $this->startByVoice();
        $this->actingAs($this->user)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]))->assertOk();

        $this->actingAs($this->user)->post(route('adaptive.interviews.stage.to-chat', [$this->simulation, $hr]));
        $this->assertSame(1, $this->user->fresh()->voice_interviews_used);
    }

    public function test_test_accounts_have_every_voice_option_without_completing_paths(): void
    {
        $this->fakeSecret();
        $demo = User::factory()->create(['email' => 'office.demo@example.com']);
        $demo->assignRole('Student');
        config(['ai.voice.unrestricted_emails' => ['office.demo@example.com']]);
        $simulation = InterviewSimulation::create(['user_id' => $demo->id, 'job_title' => 'Técnico de soporte de TI', 'area' => 'soporte', 'seniority' => 'junior', 'status' => 'generating']);
        GenerateInterviewPlanJob::dispatchSync($simulation->id);

        foreach (['hr_interview', 'technical_interview'] as $type) {
            $stage = $simulation->stages()->where('type', $type)->firstOrFail();
            $stage->update(['status' => 'available']);
            $this->actingAs($demo)->post(route('adaptive.interviews.stage.start', [$simulation, $stage]), ['mode' => 'voice']);
            $this->assertSame('voice', $stage->fresh()->content['mode']);
            // Sin límite de intentos por entrevista.
            foreach (range(1, 3) as $attempt) {
                $this->actingAs($demo)->postJson(route('adaptive.interviews.stage.voice.session', [$simulation, $stage]))->assertOk();
                config(['ai.voice.daily_limit' => 100]);
            }
        }
        $this->assertSame(0, $demo->fresh()->voice_interviews_used);
    }

    public function test_other_people_cannot_use_the_call(): void
    {
        $hr = $this->startByVoice();
        $other = User::factory()->create();
        $other->assignRole('Student');

        $this->actingAs($other)->postJson(route('adaptive.interviews.stage.voice.session', [$this->simulation, $hr]))->assertNotFound();
    }

    public function test_the_voice_agent_only_accepts_openai_realtime_models(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Admin');

        $this->actingAs($superAdmin)->put(route('admin.ai-orchestrator.update', 'voice_interviewer'), ['agent' => 'voice_interviewer', 'enabled' => 1, 'provider' => 'openai', 'model' => 'gpt-4o-mini'])
            ->assertSessionHasErrors('model');
        $this->actingAs($superAdmin)->put(route('admin.ai-orchestrator.update', 'voice_interviewer'), ['agent' => 'voice_interviewer', 'enabled' => 1, 'provider' => 'openai', 'model' => 'gpt-realtime-2.1', 'max_tokens' => 5000])
            ->assertSessionHasErrors('max_tokens');
        $this->actingAs($superAdmin)->put(route('admin.ai-orchestrator.update', 'voice_interviewer'), ['agent' => 'voice_interviewer', 'enabled' => 1, 'provider' => 'openai', 'model' => 'gpt-realtime-2.1', 'max_tokens' => 400])
            ->assertSessionHasNoErrors();
    }
}
