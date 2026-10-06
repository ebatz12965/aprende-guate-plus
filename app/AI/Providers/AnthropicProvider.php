<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * API de mensajes de Anthropic (Claude). La salida estructurada se obtiene obligando
 * al modelo a usar una herramienta cuyo esquema de entrada es el esquema pedido.
 */
class AnthropicProvider implements AiProvider
{
    private const VERSION = '2023-06-01';

    public function __construct(private readonly array $config) {}

    public function complete(string $model, array $messages, ?array $schema, int $maxTokens, float $temperature, int $timeout): array
    {
        try {
            $system = collect($messages)->where('role', 'system')->pluck('content')->implode("\n\n");
            $payload = [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'messages' => collect($messages)->where('role', '!=', 'system')->map(fn ($message) => ['role' => $message['role'] === 'assistant' ? 'assistant' : 'user', 'content' => (string) $message['content']])->values()->all(),
            ];
            if ($system !== '') {
                $payload['system'] = $system;
            }
            $tool = $schema ? substr(preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $schema['name']), 0, 64) : null;
            if ($schema) {
                $payload['tools'] = [['name' => $tool, 'description' => 'Entrega la respuesta con exactamente esta estructura.', 'input_schema' => $schema['schema']]];
                $payload['tool_choice'] = ['type' => 'tool', 'name' => $tool];
            }

            $response = $this->client()->timeout($timeout)->retry(config('ai.retry_times'), 300, null, false)->post('/messages', $payload);
            $blocks = collect($response->json('content', []));
            $content = $schema
                ? (($input = $blocks->firstWhere('type', 'tool_use')['input'] ?? null) !== null ? json_encode($input, JSON_UNESCAPED_UNICODE) : null)
                : $blocks->where('type', 'text')->pluck('text')->implode('');

            return ['successful' => $response->successful() && $content !== null, 'content' => $content, 'usage' => (array) $response->json('usage', []), 'status' => $response->status(), 'error' => $response->successful() ? null : (string) data_get($response->json(), 'error.message', 'Proveedor no disponible')];
        } catch (Throwable $exception) {
            return ['successful' => false, 'content' => null, 'usage' => [], 'status' => 0, 'error' => $exception->getMessage()];
        }
    }

    /** @return array<int, string> */
    public function models(): array
    {
        $response = $this->client()->timeout(15)->get('/models', ['limit' => 100]);

        return $response->successful() ? collect($response->json('data', []))->pluck('id')->filter()->values()->all() : [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->config['base_url'], '/'))
            ->withHeaders(['x-api-key' => (string) $this->config['key'], 'anthropic-version' => self::VERSION])
            ->acceptJson()->connectTimeout(config('ai.connect_timeout'));
    }
}
