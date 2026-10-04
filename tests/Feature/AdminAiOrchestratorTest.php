<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAiOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_ai_orchestrator(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        AiRequest::create(['request_uuid' => (string) Str::uuid(), 'agent_slug' => 'tutor', 'provider' => 'openai', 'model' => 'test-model', 'operation' => 'tutor_reply', 'input_hash' => hash('sha256', 'input'), 'status' => 'completed', 'total_tokens' => 120, 'completed_at' => now()]);
        $this->actingAs($admin)->get(route('admin.ai-orchestrator.index'))->assertOk()->assertSee('Orquestador de IA')->assertSee('Tutor IA');
    }

    public function test_student_cannot_view_ai_orchestrator(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.ai-orchestrator.index'))->assertForbidden();
    }
}
