<?php

namespace App\AI;

use App\Models\AiAgent;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Throwable;

/**
 * Configuración efectiva de cada agente: valores de config/ai.php con los cambios
 * guardados desde administración (tabla ai_agents) encima.
 */
class AgentCatalog
{
    public const AGENTS = ['career_profiler', 'learning_path', 'curriculum', 'content_classifier', 'theory_content', 'tutorial_script', 'exercise_generator', 'file_generator', 'evaluator', 'tutor', 'interviewer', 'interview_evaluator', 'employability', 'mentor_application_assessor'];

    private const CACHE_KEY = 'ai.agent_overrides';

    public function configuration(string $slug): array
    {
        if (! in_array($slug, self::AGENTS, true)) {
            throw new InvalidArgumentException("Agente IA no reconocido: {$slug}");
        }

        $defaults = config("ai.agents.{$slug}", []);
        $override = $this->overrides()[$slug] ?? [];
        $planned = (bool) ($defaults['planned'] ?? false);

        return array_merge($defaults, [
            'enabled' => ! $planned && ($override['enabled'] ?? $defaults['enabled'] ?? false),
            'model' => $override['model'] ?? $defaults['model'] ?? config('ai.model'),
            // null: cada operación usa su propio límite de tokens.
            'max_tokens_override' => $override['max_tokens'] ?? null,
            'planned' => $planned,
            'customized' => $override !== [],
        ]);
    }

    public function all(): array
    {
        return collect(self::AGENTS)->mapWithKeys(fn (string $slug) => [$slug => $this->configuration($slug)])->all();
    }

    /** Guarda los cambios de un agente; un valor igual al de config/ai.php se guarda como null. */
    public function saveOverride(string $slug, bool $enabled, ?string $model, ?int $maxTokens, ?int $editorId): void
    {
        $defaults = config("ai.agents.{$slug}", []);
        $defaultModel = $defaults['model'] ?? config('ai.model');

        AiAgent::updateOrCreate(['slug' => $slug], [
            'enabled' => $enabled === (bool) ($defaults['enabled'] ?? false) ? null : $enabled,
            'model' => blank($model) || $model === $defaultModel ? null : $model,
            'max_tokens' => $maxTokens,
            'updated_by' => $editorId,
        ]);

        Cache::forget(self::CACHE_KEY);
    }

    public function resetOverride(string $slug): void
    {
        AiAgent::where('slug', $slug)->delete();
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array{enabled?: bool, model?: string, max_tokens?: int}> */
    private function overrides(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => AiAgent::query()->get()->mapWithKeys(fn (AiAgent $agent) => [
                $agent->slug => array_filter(['enabled' => $agent->enabled, 'model' => $agent->model, 'max_tokens' => $agent->max_tokens], fn ($value) => $value !== null),
            ])->all());
        } catch (Throwable) {
            // Sin base de datos disponible (instalación o migraciones pendientes) se usa config/ai.php.
            return [];
        }
    }
}
