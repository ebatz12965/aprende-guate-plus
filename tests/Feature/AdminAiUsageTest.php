<?php

namespace Tests\Feature;

use App\Models\AiTokenUsage;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAiUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_token_usage_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        $student = User::factory()->create(['name' => 'Estudiante con consumo']);
        PlatformSetting::setInteger('ai_token_budget', 10000);
        AiTokenUsage::create(['user_id' => $student->id, 'operation' => 'topic_lesson', 'model' => 'modelo-prueba', 'prompt_tokens' => 300, 'completion_tokens' => 200, 'total_tokens' => 500, 'successful' => true, 'duration_ms' => 400]);

        $this->actingAs($admin)->get(route('admin.ai-usage.index'))
            ->assertOk()
            ->assertSee('Consumo de tokens')
            ->assertSee('Estudiante con consumo')
            ->assertSee('9,500');
    }

    public function test_non_administrator_cannot_view_token_usage_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.ai-usage.index'))
            ->assertForbidden();
    }
}
