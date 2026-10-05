<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\DiagnosticAttempt;
use App\Models\LearningPath;
use App\Models\MentorshipSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_prioritizes_the_phase_in_progress(): void
    {
        $user = User::factory()->create();
        $path = $this->pathWithItems($user, ['in_progress', 'pending']);
        $item = $path->items()->where('position', 1)->first();

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('Tu meta: Conseguir empleo como auxiliar contable')
            ->assertSee('Sigue con la fase 1: Fase número 1')
            ->assertSee('Fase número 1')
            ->assertDontSee('Fase número 2')
            ->assertSee(route('adaptive.path.item.show', $item), false)
            ->assertSee('Tu próximo reto se desbloquea al 25 %')
            ->assertSee('Te falta 1 fase');
    }

    public function test_new_student_sees_a_single_start_action(): void
    {
        $user = User::factory()->create(['name' => 'Ana López']);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('Hola, Ana')
            ->assertSee('Empezar diagnóstico')
            ->assertDontSee('0 de 0 fases')
            ->assertDontSee('platformTour');
    }

    public function test_generating_and_failed_attempts_have_their_own_states(): void
    {
        $user = User::factory()->create();
        $attempt = DiagnosticAttempt::create(['user_id' => $user->id, 'primary_answer' => 'Aprender Excel', 'answers' => [0, 1, 2, 3], 'status' => 'generating']);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('Estamos armando tu ruta')
            ->assertSee('Aprender Excel');

        $attempt->update(['status' => 'failed']);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('No pudimos generar tu ruta')
            ->assertSee(route('adaptive.diagnostic.retry', $attempt), false);
    }

    public function test_completed_path_is_celebrated_instead_of_restarting(): void
    {
        $user = User::factory()->create();
        $path = $this->pathWithItems($user, ['completed', 'completed']);
        $path->update(['status' => 'completed']);
        Challenge::create(['user_id' => $user->id, 'learning_path_id' => $path->id, 'milestone' => 100, 'title' => 'Reto final de conciliación', 'area' => 'Contabilidad', 'level' => 'Inicial', 'description' => 'Concilia un estado de cuenta.', 'generation_status' => 'completed']);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('¡Completaste tu ruta!')
            ->assertSee('Completar mi perfil profesional')
            ->assertSee('2 fases completadas')
            ->assertSee('Reto final de conciliación')
            ->assertDontSee('Empezar diagnóstico');
    }

    public function test_new_diagnostic_after_a_path_shows_a_notice_and_keeps_the_path(): void
    {
        $user = User::factory()->create();
        $this->pathWithItems($user, ['in_progress']);
        DiagnosticAttempt::create(['user_id' => $user->id, 'primary_answer' => 'Cambiar de área', 'answers' => [0, 1, 2, 3], 'status' => 'generating']);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('Estamos generando tu nueva ruta')
            ->assertSee('Continuar fase 1');
    }

    public function test_confirmed_mentorship_shows_date_in_guatemala_time(): void
    {
        $user = User::factory()->create();
        $this->pathWithItems($user, ['in_progress']);
        MentorshipSession::create(['student_id' => $user->id, 'mentor_id' => User::factory()->create(['name' => 'Luis Pérez'])->id, 'topic' => 'Preparar mi entrevista', 'status' => 'scheduled', 'scheduled_at' => now()->addDays(2)->setTime(15, 0)]);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('Preparar mi entrevista')
            ->assertSee('3:00 p. m. (hora de Guatemala) con Luis Pérez');
    }

    public function test_path_without_phases_offers_to_redo_the_diagnostic(): void
    {
        $user = User::factory()->create();
        LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta vacía', 'goal' => 'Meta', 'status' => 'active']);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('Tu ruta quedó sin fases')
            ->assertDontSee('Te falta 1 fase')
            ->assertDontSee('0 de 0 fases');
    }

    public function test_accepted_session_without_date_and_missing_mentor_read_naturally(): void
    {
        $user = User::factory()->create();
        $this->pathWithItems($user, ['in_progress']);
        $mentor = User::factory()->create(['name' => 'Luis Pérez']);
        MentorshipSession::create(['student_id' => $user->id, 'mentor_id' => $mentor->id, 'topic' => 'Revisar mi CV', 'status' => 'accepted']);

        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()
            ->assertSee('Revisar mi CV')
            ->assertSee('Luis Pérez aceptó tu solicitud. La fecha está por confirmar.')
            ->assertDontSee('Pedir una mentoría');
    }

    public function test_status_endpoint_changes_when_the_path_is_ready(): void
    {
        $user = User::factory()->create();
        $attempt = DiagnosticAttempt::create(['user_id' => $user->id, 'primary_answer' => 'Excel', 'answers' => [0, 1, 2, 3], 'status' => 'generating']);

        $before = $this->actingAs($user)->getJson(route('adaptive.dashboard.status'))->assertOk()->json('key');
        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertSee('mi-panel\/estado', false);

        $attempt->update(['status' => 'completed']);
        $this->pathWithItems($user, ['in_progress']);

        $this->assertNotSame($before, $this->actingAs($user)->getJson(route('adaptive.dashboard.status'))->json('key'));
    }

    public function test_redoing_the_diagnostic_warns_that_the_path_will_be_replaced(): void
    {
        $user = User::factory()->create();
        $this->pathWithItems($user, ['completed', 'in_progress']);

        $this->actingAs($user)->get(route('adaptive.diagnostic'))->assertOk()
            ->assertSee('1 de 2 fases')
            ->assertSee('se creará una ruta nueva que la reemplaza')
            ->assertSee('Volver a mi panel')
            ->assertDontSee('DIAGNÓSTICO PERSONALIZADO');
    }

    public function test_info_messages_are_shown(): void
    {
        $user = User::factory()->create();
        $attempt = DiagnosticAttempt::create(['user_id' => $user->id, 'primary_answer' => 'Excel', 'answers' => [0, 1, 2, 3], 'status' => 'completed']);

        $this->actingAs($user)->followingRedirects()->get(route('adaptive.diagnostic.questions', $attempt))->assertOk()
            ->assertSee('Este cuestionario ya fue procesado');
    }

    private function pathWithItems(User $user, array $statuses): LearningPath
    {
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta contable', 'goal' => 'Conseguir empleo como auxiliar contable', 'status' => 'active']);
        foreach ($statuses as $index => $status) {
            $position = $index + 1;
            $path->items()->create(['title' => 'Fase número '.$position, 'area' => 'Base', 'description' => 'Descripción', 'position' => $position, 'status' => $status, 'quiz' => ['question' => 'Q', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
        }

        return $path;
    }
}
