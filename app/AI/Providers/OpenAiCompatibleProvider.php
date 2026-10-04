<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiCompatibleProvider implements AiProvider
{
    public function complete(string $model, array $messages, ?array $schema, int $maxTokens, float $temperature, int $timeout): array
    {
        try {
            $payload = ['model' => $model, 'messages' => $messages, 'temperature' => $temperature, 'max_tokens' => $maxTokens];
            if ($schema) {
                $payload['response_format'] = ['type' => 'json_schema', 'json_schema' => ['name' => $schema['name'], 'strict' => true, 'schema' => $schema['schema']]];
            }
            $response = Http::baseUrl((config('ai.providers.openai.base_url') ?: config('ai.base_url')))
                ->withToken((config('ai.providers.openai.key') ?: config('ai.key')))->acceptJson()
                ->connectTimeout(config('ai.connect_timeout'))->timeout($timeout)
                ->retry(config('ai.retry_times'), 300, null, false)->post('/chat/completions', $payload);

            return ['successful' => $response->successful(), 'content' => data_get($response->json(), 'choices.0.message.content'), 'usage' => (array) $response->json('usage', []), 'status' => $response->status(), 'error' => $response->successful() ? null : (string) data_get($response->json(), 'error.message', 'Proveedor no disponible')];
        } catch (Throwable $exception) {
            return ['successful' => false, 'content' => null, 'usage' => [], 'status' => 0, 'error' => $exception->getMessage()];
        }
    }
}
