<?php

namespace Tests\Feature;

use App\AI\AgentCatalog;
use App\Models\AiAgent;
use App\Models\AiRequest;
use App\Models\User;
use App\Services\AiTutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAiOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));

        return $admin;
    }

    public function test_administrator_can_view_ai_orchestrator(): void
    {
        AiRequest::create(['request_uuid' => (string) Str::uuid(), 'agent_slug' => 'tutor', 'provider' => 'openai', 'model' => 'test-model', 'operation' => 'tutor_reply', 'input_hash' => hash('sha256', 'input'), 'status' => 'completed', 'total_tokens' => 120, 'completed_at' => now()]);

        $this->actingAs($this->admin())->get(route('admin.ai-orchestrator.index'))
            ->assertOk()
            ->assertSee('Orquestador de IA')
            ->assertSee('Tutor IA')
            ->assertSee('Sin módulo de producto');
    }

    public function test_student_cannot_view_or_change_ai_orchestrator(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.ai-orchestrator.index'))->assertForbidden();
        $this->actingAs($student)->put(route('admin.ai-orchestrator.update', 'tutor'), ['enabled' => '0'])->assertForbidden();
    }

    public function test_disabling_an_agent_stops_provider_calls(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta del modelo.']]]])]);

        $this->actingAs($this->admin())->put(route('admin.ai-orchestrator.update', 'tutor'), ['enabled' => '0'])
            ->assertRedirect(route('admin.ai-orchestrator.index'));

        $reply = app(AiTutorService::class)->reply([['role' => 'user', 'content' => 'Hola']], 'Estudiante');
        $this->assertStringContainsString('modo demostración', $reply);
        Http::assertNothingSent();
    }

    public function test_model_and_token_limit_overrides_are_sent_to_the_provider(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta del modelo.']]]])]);

        $this->actingAs($this->admin())->put(route('admin.ai-orchestrator.update', 'tutor'), ['enabled' => '1', 'model' => 'gpt-4.1-mini', 'max_tokens' => 450])
            ->assertSessionHasNoErrors();

        app(AiTutorService::class)->reply([['role' => 'user', 'content' => 'Hola']], 'Estudiante');
        Http::assertSent(fn ($request) => $request['model'] === 'gpt-4.1-mini' && $request['max_completion_tokens'] === 450);
    }

    public function test_values_equal_to_config_are_not_stored_and_reset_restores_defaults(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.ai-orchestrator.update', 'tutor'), ['enabled' => '1', 'model' => config('ai.model')]);
        $override = AiAgent::where('slug', 'tutor')->firstOrFail();
        $this->assertNull($override->enabled);
        $this->assertNull($override->model);

        $this->actingAs($admin)->put(route('admin.ai-orchestrator.update', 'tutor'), ['enabled' => '0']);
        $this->actingAs($admin)->delete(route('admin.ai-orchestrator.reset', 'tutor'))->assertRedirect();
        $this->assertDatabaseMissing('ai_agents', ['slug' => 'tutor']);
        $this->assertTrue(app(AgentCatalog::class)->configuration('tutor')['enabled']);
    }

    public function test_planned_agents_cannot_be_enabled_and_unknown_agents_return_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.ai-orchestrator.update', 'interviewer'), ['enabled' => '1'])
            ->assertSessionHasErrors('enabled');
        $this->assertFalse(app(AgentCatalog::class)->configuration('interviewer')['enabled']);

        $this->actingAs($admin)->put(route('admin.ai-orchestrator.update', 'no-existe'), ['enabled' => '1'])->assertNotFound();
        $this->actingAs($admin)->put(route('admin.ai-orchestrator.update', 'tutor'), ['enabled' => '1', 'model' => 'modelo con espacios'])->assertSessionHasErrors('model');
    }
}
