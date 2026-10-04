<?php

namespace App\AI;

use App\AI\Contracts\AiProvider;
use App\AI\Providers\OpenAiCompatibleProvider;
use App\Models\AiRequest;
use App\Models\AiTokenUsage;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiGateway
{
    public function __construct(private readonly AgentCatalog $catalog, private readonly OutputGuard $guard) {}

    /**
     * Tiempo máximo (segundos) que necesita un job que hace $calls llamadas secuenciales al proveedor,
     * considerando conexión, generación, reintentos HTTP y un margen para el trabajo en base de datos.
     * Debe mantenerse por debajo de queue.connections.*.retry_after.
     */
    public static function jobTimeout(int $calls): int
    {
        $perCall = ((int) config('ai.connect_timeout') + (int) config('ai.generation_timeout')) * max(1, (int) config('ai.retry_times')) + 1;

        return max(1, $calls) * $perCall + 20;
    }

    public function text(string $agent, string $system, array $messages, string $operation, ?int $userId = null, int $maxTokens = 180, float $temperature = 0.35): ?string
    {
        $result = $this->complete($agent, array_merge([['role' => 'system', 'content' => $system]], $messages), null, $operation, $userId, $maxTokens, $temperature);
        return $result['successful'] ? $this->guard->sanitizeText($result['content']) : null;
    }

    public function structured(string $agent, string $prompt, array $schema, string $operation, ?int $userId, int $maxTokens): ?array
    {
        $result = $this->complete($agent, [
            ['role' => 'system', 'content' => 'Responde únicamente con datos que cumplan el esquema. No incluyas Markdown, instrucciones internas ni información sensible.'],
            ['role' => 'user', 'content' => $prompt],
        ], ['name' => $operation, 'schema' => $schema], $operation, $userId, $maxTokens, 0.2);
        return $result['successful'] ? $this->guard->decodeStructured($result['content']) : null;
    }

    private function complete(string $agent, array $messages, ?array $schema, string $operation, ?int $userId, int $maxTokens, float $temperature): array
    {
        $agentConfig = $this->catalog->configuration($agent);
        if (! ($agentConfig['enabled'] ?? false)) {
            Log::warning('ai.gateway.agent_disabled', ['agent' => $agent, 'operation' => $operation]);
            return ['successful' => false, 'content' => null];
        }

        $provider = $agentConfig['provider'] ?? config('ai.default_provider');
        $model = $agentConfig['model'] ?? config('ai.model');
        $request = $this->startRequest($agent, $provider, $model, $operation, $userId, $messages);
        if (! (config("ai.providers.{$provider}.key") ?: config('ai.key'))) {
            $this->finishRequest($request, [], false, 0, 'No hay credencial configurada para el proveedor.');
            return ['successful' => false, 'content' => null];
        }
        if ($this->budgetExhausted()) {
            Log::warning('ai.gateway.budget_exhausted', ['agent' => $agent, 'operation' => $operation]);
            $this->finishRequest($request, [], false, 0, 'Se alcanzó el presupuesto de tokens configurado.');
            return ['successful' => false, 'content' => null];
        }
        $startedAt = hrtime(true);
        $response = $this->provider($provider)->complete($model, $messages, $schema, $maxTokens, $temperature);
        $duration = (int) ((hrtime(true) - $startedAt) / 1_000_000);
        $this->recordUsage((array) $response['usage'], $operation, $agent, $provider, $model, $userId, $duration, (bool) $response['successful']);
        $this->finishRequest($request, (array) $response['usage'], (bool) $response['successful'], $duration, $response['error'] ?? null, $response['content'] ?? null);
        Log::info('ai.gateway.request', ['agent' => $agent, 'operation' => $operation, 'provider' => $provider, 'model' => $model, 'duration_ms' => $duration, 'successful' => $response['successful']]);
        return $response;
    }

    /** Un presupuesto de 0 significa sin límite. */
    private function budgetExhausted(): bool
    {
        $budget = PlatformSetting::aiTokenBudget();
        return $budget > 0 && (int) AiTokenUsage::sum('total_tokens') >= $budget;
    }

    private function provider(string $provider): AiProvider
    {
        return match ($provider) {
            'openai' => app(OpenAiCompatibleProvider::class),
            default => throw new \RuntimeException("El proveedor {$provider} no está habilitado."),
        };
    }

    private function startRequest(string $agent, string $provider, string $model, string $operation, ?int $userId, array $messages): ?AiRequest
    {
        try {
            return AiRequest::create(['request_uuid' => (string) Str::uuid(), 'user_id' => $userId, 'agent_slug' => $agent, 'provider' => $provider, 'model' => $model, 'operation' => $operation, 'input_hash' => hash('sha256', json_encode($messages, JSON_UNESCAPED_UNICODE)), 'status' => 'processing']);
        } catch (Throwable) { return null; }
    }

    private function finishRequest(?AiRequest $request, array $usage, bool $successful, int $duration, ?string $error = null, ?string $content = null): void
    {
        if (! $request) return;
        $input = max(0, (int) ($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0));
        $output = max(0, (int) ($usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0));
        $request->update(['status' => $successful ? 'completed' : 'failed', 'input_tokens' => $input, 'output_tokens' => $output, 'total_tokens' => max(0, (int) ($usage['total_tokens'] ?? ($input + $output))), 'duration_ms' => $duration, 'output_hash' => $content ? hash('sha256', $content) : null, 'failure_reason' => $successful ? null : Str::limit((string) $error, 500), 'completed_at' => now()]);
    }

    private function recordUsage(array $usage, string $operation, string $agent, string $provider, string $model, ?int $userId, int $duration, bool $successful): void
    {
        $input = max(0, (int) ($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0));
        $output = max(0, (int) ($usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0));
        try {
            AiTokenUsage::create(['user_id' => $userId, 'operation' => $operation, 'agent_slug' => $agent, 'provider' => $provider, 'model' => $model, 'prompt_tokens' => $input, 'completion_tokens' => $output, 'total_tokens' => max(0, (int) ($usage['total_tokens'] ?? ($input + $output))), 'successful' => $successful, 'duration_ms' => $duration]);
        } catch (Throwable $exception) { Log::warning('ai.gateway.usage_not_recorded', ['reason' => $exception->getMessage()]); }
    }
}