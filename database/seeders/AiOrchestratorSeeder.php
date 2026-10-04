<?php

namespace Database\Seeders;

use App\AI\AgentCatalog;
use App\Models\AiAgent;
use App\Models\AiProvider;
use Illuminate\Database\Seeder;

class AiOrchestratorSeeder extends Seeder
{
    public function run(): void
    {
        AiProvider::updateOrCreate(['slug' => 'openai'], ['name' => 'OpenAI', 'enabled' => (bool) config('ai.providers.openai.enabled'), 'settings' => ['credential_source' => 'environment']]);
        foreach (app(AgentCatalog::class)->all() as $slug => $agent) {
            AiAgent::updateOrCreate(['slug' => $slug], ['name' => $agent['name'], 'description' => $agent['description'], 'provider' => $agent['provider'], 'model' => $agent['model'] ?? config('ai.model'), 'max_tokens' => $agent['max_tokens'], 'enabled' => $agent['enabled'], 'settings' => ['managed_by' => 'config/ai.php']]);
        }
    }
}
