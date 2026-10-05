<?php

namespace Tests\Feature;

use App\AI\AiGateway;
use App\Models\AiAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiProvidersTest extends TestCase
{
    use RefreshDatabase;

    private array $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['ok'], 'properties' => ['ok' => ['type' => 'boolean']]];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.providers.gemini.key', 'gemini-key');
        config()->set('ai.providers.gemini.base_url', 'https://gemini.test/v1beta/openai');
        config()->set('ai.providers.deepseek.key', 'deepseek-key');
        config()->set('ai.providers.deepseek.base_url', 'https://deepseek.test');
        config()->set('ai.providers.anthropic.key', 'anthropic-key');
        config()->set('ai.providers.anthropic.base_url', 'https://anthropic.test/v1');
    }

    public function test_reasoning_models_get_no_temperature_and_room_to_think(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]])]);
        $this->useAgent('goal_analyst', 'openai', 'gpt-5-mini');

        $this->assertSame(['ok' => true], app(AiGateway::class)->structured('goal_analyst', 'Hola', $this->schema, 'prueba', null, 1000));

        Http::assertSent(fn (Request $request) => ! array_key_exists('temperature', $request->data()) && $request['max_completion_tokens'] === 7000
            && $request['response_format']['type'] === 'json_schema');
    }

    public function test_gemini_uses_its_own_endpoint_key_and_strict_schema(): void
    {
        Http::fake(['gemini.test/*' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]])]);
        $this->useAgent('goal_analyst', 'gemini', 'gemini-2.5-flash');

        $this->assertSame(['ok' => true], app(AiGateway::class)->structured('goal_analyst', 'Hola', $this->schema, 'prueba', null, 1000));

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://gemini.test/v1beta/openai/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer gemini-key') && $request['max_tokens'] === 7000 && $request['response_format']['type'] === 'json_schema');
    }

    public function test_deepseek_receives_json_mode_with_the_schema_in_the_instructions(): void
    {
        Http::fake(['deepseek.test/*' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]])]);
        $this->useAgent('goal_analyst', 'deepseek', 'deepseek-chat');

        $this->assertSame(['ok' => true], app(AiGateway::class)->structured('goal_analyst', 'Hola', $this->schema, 'prueba', null, 1000));

        Http::assertSent(fn (Request $request) => $request['response_format'] === ['type' => 'json_object']
            && str_contains($request['messages'][0]['content'], '"required":["ok"]') && $request['max_tokens'] === 1000);
    }

    public function test_anthropic_forces_a_tool_with_the_schema_and_reads_its_input(): void
    {
        Http::fake(['anthropic.test/*' => Http::response(['content' => [['type' => 'tool_use', 'name' => 'prueba', 'input' => ['ok' => true]]], 'usage' => ['input_tokens' => 12, 'output_tokens' => 5]])]);
        $this->useAgent('goal_analyst', 'anthropic', 'claude-sonnet-5-5');

        $this->assertSame(['ok' => true], app(AiGateway::class)->structured('goal_analyst', 'Hola', $this->schema, 'prueba', null, 1000));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://anthropic.test/v1/messages' && $request->hasHeader('x-api-key', 'anthropic-key')
            && $request['tool_choice'] === ['type' => 'tool', 'name' => 'prueba'] && $request['tools'][0]['input_schema'] === $this->schema
            && str_contains($request['system'], 'esquema'));
        $this->assertDatabaseHas('ai_token_usages', ['provider' => 'anthropic', 'prompt_tokens' => 12, 'completion_tokens' => 5]);
    }

    public function test_orchestrator_lists_live_models_and_saves_provider_per_agent(): void
    {
        Http::fake(['gemini.test/*' => Http::response(['data' => [['id' => 'models/gemini-2.5-pro'], ['id' => 'models/text-embedding-004']]]), '*' => Http::response(['data' => []])]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.ai-orchestrator.index'))->assertOk()
            ->assertSee('Proveedores de IA')
            ->assertSee('gemini-2.5-pro')
            ->assertDontSee('text-embedding-004');

        $this->actingAs($admin)->put(route('admin.ai-orchestrator.update', 'goal_analyst'), ['enabled' => '1', 'provider' => 'gemini', 'model' => 'gemini-2.5-pro'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ai_agents', ['slug' => 'goal_analyst', 'provider' => 'gemini', 'model' => 'gemini-2.5-pro']);
    }

    public function test_a_provider_without_key_cannot_be_selected(): void
    {
        config()->set('ai.providers.deepseek.key', null);

        $this->actingAs($this->admin())->put(route('admin.ai-orchestrator.update', 'goal_analyst'), ['enabled' => '1', 'provider' => 'deepseek', 'model' => 'deepseek-chat'])
            ->assertSessionHasErrors('provider');
        $this->assertDatabaseMissing('ai_agents', ['slug' => 'goal_analyst']);
    }

    private function useAgent(string $slug, string $provider, string $model): void
    {
        AiAgent::create(['slug' => $slug, 'provider' => $provider, 'model' => $model]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));

        return $admin;
    }
}
