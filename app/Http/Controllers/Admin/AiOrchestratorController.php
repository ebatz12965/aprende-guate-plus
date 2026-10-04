<?php

namespace App\Http\Controllers\Admin;

use App\AI\AgentCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAiAgentRequest;
use App\Models\AiRequest;
use App\Models\AiTokenUsage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiOrchestratorController extends Controller
{
    public function index(AgentCatalog $catalog): View
    {
        $configured = (bool) (config('ai.providers.openai.key') ?: config('ai.key'));
        $metrics = AiRequest::query()
            ->selectRaw('agent_slug, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', ['completed'])
            ->groupBy('agent_slug')
            ->get()
            ->keyBy('agent_slug');

        $agents = collect($catalog->all())->map(fn (array $agent, string $slug) => [
            'slug' => $slug,
            'name' => $agent['name'],
            'description' => $agent['description'],
            'provider' => $agent['provider'],
            'model' => $agent['model'],
            'default_model' => config("ai.agents.{$slug}.model") ?? config('ai.model'),
            'max_tokens_override' => $agent['max_tokens_override'],
            'enabled' => (bool) $agent['enabled'],
            'planned' => (bool) $agent['planned'],
            'customized' => (bool) $agent['customized'],
            'requests' => (int) ($metrics[$slug]->requests ?? 0),
            'tokens' => (int) ($metrics[$slug]->tokens ?? 0),
            'completed' => (int) ($metrics[$slug]->completed ?? 0),
        ])->values();

        return view('admin.ai-orchestrator.index', ['providerConfigured' => $configured, 'providerEnabled' => (bool) config('ai.providers.openai.enabled'), 'agents' => $agents, 'totalRequests' => AiRequest::count(), 'totalTokens' => (int) AiTokenUsage::sum('total_tokens'), 'failedRequests' => AiRequest::where('status', 'failed')->count(), 'recentRequests' => AiRequest::with('user:id,name,email')->latest()->limit(12)->get()]);
    }

    public function update(UpdateAiAgentRequest $request, AgentCatalog $catalog, string $agent): RedirectResponse
    {
        $data = $request->validated();
        $catalog->saveOverride($agent, $request->boolean('enabled'), $data['model'] ?? null, $data['max_tokens'] ?? null, $request->user()->id);

        return redirect()->route('admin.ai-orchestrator.index')->with('success', 'Configuración del agente «'.config("ai.agents.{$agent}.name").'» actualizada.');
    }

    public function reset(AgentCatalog $catalog, string $agent): RedirectResponse
    {
        abort_unless(in_array($agent, AgentCatalog::AGENTS, true), 404);
        $catalog->resetOverride($agent);

        return redirect()->route('admin.ai-orchestrator.index')->with('success', 'El agente «'.config("ai.agents.{$agent}.name").'» volvió a los valores de config/ai.php.');
    }
}
