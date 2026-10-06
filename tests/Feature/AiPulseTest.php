<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiPulseTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ai_tab_shows_live_aggregates_by_user_model_agent_and_failures(): void
    {
        $admin = $this->admin();
        $ana = User::factory()->create(['name' => 'Ana López']);
        $luis = User::factory()->create(['name' => 'Luis Pérez']);
        $this->request($ana, 'gpt-5.4-mini', 'theory_content', 3000);
        $this->request($ana, 'gpt-5.4-mini', 'tutor', 500);
        $this->request($luis, 'gpt-4o-mini', 'tutor', 800);
        $this->request($luis, 'gpt-4o-mini', 'evaluator', 0, 'failed', 'cURL error 28: Operation timed out');

        $this->actingAs($admin)->get(route('admin.ai-pulse.index'))->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('Inteligencia artificial')
            ->assertSee('Tokens en el tiempo')
            ->assertSee('Agrega los precios por modelo');

        $data = $this->actingAs($admin)->getJson(route('admin.ai-pulse.data', ['range' => 1440]))->assertOk()->json();
        $this->assertSame(4300, $data['kpis']['tokens']);
        $this->assertSame(4, $data['kpis']['requests']);
        $this->assertSame(75.0, (float) $data['kpis']['ok_rate']);
        $this->assertSame(['Ana López', 'Luis Pérez'], array_column($data['users'], 'name'));
        $this->assertSame('gpt-5.4-mini', $data['models'][0]['id']);
        $this->assertSame('Generador de teoría', $data['agents'][0]['name']);
        $this->assertSame([['reason' => 'Tiempo de espera agotado', 'count' => 1]], $data['status']['reasons']);
        $this->assertCount(4, $data['feed']);
        $this->assertNull($data['kpis']['cost']);
    }

    public function test_detail_and_filter_by_provider(): void
    {
        $admin = $this->admin();
        $ana = User::factory()->create(['name' => 'Ana López']);
        $request = $this->request($ana, 'gpt-5.4-mini', 'theory_content', 3000);
        $this->request($ana, 'gemini-2.5-pro', 'content_reviewer', 2000, 'completed', null, 'gemini');

        $user = $this->actingAs($admin)->getJson(route('admin.ai-pulse.detail', ['type' => 'user', 'key' => $ana->id, 'range' => 60]))->assertOk()->json();
        $this->assertSame('Ana López', $user['title']);
        $this->assertSame('Agentes que más usa', $user['list_title']);

        $single = $this->actingAs($admin)->getJson(route('admin.ai-pulse.detail', ['type' => 'request', 'key' => $request->id]))->assertOk()->json();
        $this->assertSame('Generador de teoría', $single['title']);

        $gemini = $this->actingAs($admin)->getJson(route('admin.ai-pulse.data', ['range' => 60, 'provider' => 'gemini']))->assertOk()->json();
        $this->assertSame(2000, $gemini['kpis']['tokens']);
    }

    public function test_prices_turn_tokens_into_an_estimated_cost(): void
    {
        $admin = $this->admin();
        $this->request(User::factory()->create(), 'gpt-5.4-mini', 'tutor', 1000);

        $this->actingAs($admin)->put(route('admin.ai-orchestrator.prices'), ['prices' => [['model' => 'gpt-5.4-mini', 'input' => '1', 'output' => '4']]])
            ->assertRedirect(route('admin.ai-orchestrator.index'));
        $this->actingAs($admin)->get(route('admin.ai-orchestrator.index'))->assertOk()->assertSee('Precios por modelo')->assertSee('value="4"', false);

        // 400 de entrada a US$ 1 y 600 de salida a US$ 4 por millón.
        $data = $this->actingAs($admin)->getJson(route('admin.ai-pulse.data'))->json();
        $this->assertEqualsWithDelta(0.0028, $data['kpis']['cost'], 0.00001);
    }

    public function test_only_administrators_can_see_the_ai_dashboard(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.ai-pulse.index'))->assertForbidden();
        $this->actingAs($student)->getJson(route('admin.ai-pulse.data'))->assertForbidden();
    }

    public function test_demo_data_refuses_to_run_outside_a_demo_database(): void
    {
        $this->artisan('ai:demo-data', ['--days' => 1])->expectsOutputToContain('solo se ejecuta en una base de demostración')->assertFailed();
        $this->assertSame(0, AiRequest::count());
    }

    private function request(User $user, string $model, string $agent, int $tokens, string $status = 'completed', ?string $failure = null, string $provider = 'openai'): AiRequest
    {
        return AiRequest::create([
            'request_uuid' => (string) Str::uuid(), 'user_id' => $user->id, 'agent_slug' => $agent, 'provider' => $provider, 'model' => $model, 'operation' => $agent,
            'input_hash' => str_repeat('a', 64), 'status' => $status, 'input_tokens' => (int) ($tokens * .4), 'output_tokens' => (int) ($tokens * .6), 'total_tokens' => $tokens,
            'duration_ms' => 2000, 'failure_reason' => $failure,
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['name' => 'Administrador']);
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));

        return $admin;
    }
}
