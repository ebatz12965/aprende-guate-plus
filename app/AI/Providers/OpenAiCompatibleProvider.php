<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * API de chat compatible con OpenAI. La usan OpenAI, Google Gemini y DeepSeek;
 * las diferencias (salida estructurada y nombre del límite de tokens) vienen de config/ai.php.
 */
class OpenAiCompatibleProvider implements AiProvider
{
    public function __construct(private readonly array $config) {}

    public function complete(string $model, array $messages, ?array $schema, int $maxTokens, float $temperature, int $timeout): array
    {
        try {
            $payload = ['model' => $model, 'messages' => $messages];
            // Los modelos de razonamiento rechazan una temperatura distinta de la predeterminada.
            if (! self::fixedTemperature($model)) {
                $payload['temperature'] = $temperature;
            }
            // El razonamiento interno consume del mismo límite: se deja margen para la respuesta visible.
            $payload[$this->config['token_parameter'] ?? 'max_tokens'] = self::thinks($model) ? $maxTokens + 6000 : $maxTokens;

            if ($schema && ($this->config['structured'] ?? 'json_schema') === 'json_schema') {
                $payload['response_format'] = ['type' => 'json_schema', 'json_schema' => ['name' => $schema['name'], 'strict' => true, 'schema' => $schema['schema']]];
            } elseif ($schema) {
                // Sin esquema estricto: se pide JSON y el esquema va en las instrucciones; la respuesta se valida después.
                $payload['response_format'] = ['type' => 'json_object'];
                array_unshift($payload['messages'], ['role' => 'system', 'content' => 'Responde solo con un objeto JSON válido que cumpla exactamente este esquema JSON: '.json_encode($schema['schema'], JSON_UNESCAPED_UNICODE)]);
            }

            $response = Http::baseUrl(rtrim((string) $this->config['base_url'], '/'))
                ->withToken((string) $this->config['key'])->acceptJson()
                ->connectTimeout(config('ai.connect_timeout'))->timeout($timeout)
                ->retry(config('ai.retry_times'), 300, null, false)->post('/chat/completions', $payload);

            return ['successful' => $response->successful(), 'content' => data_get($response->json(), 'choices.0.message.content'), 'usage' => (array) $response->json('usage', []), 'status' => $response->status(), 'error' => $response->successful() ? null : (string) data_get($response->json(), 'error.message', 'Proveedor no disponible')];
        } catch (Throwable $exception) {
            return ['successful' => false, 'content' => null, 'usage' => [], 'status' => 0, 'error' => $exception->getMessage()];
        }
    }

    /** @return array<int, string> */
    public function models(): array
    {
        $response = Http::baseUrl(rtrim((string) $this->config['base_url'], '/'))->withToken((string) $this->config['key'])->acceptJson()
            ->connectTimeout(config('ai.connect_timeout'))->timeout(15)->get('/models');

        return $response->successful()
            ? collect($response->json('data', []))->pluck('id')->filter()->map(fn ($id) => preg_replace('#^models/#', '', (string) $id))->values()->all()
            : [];
    }

    /** Modelos de razonamiento de OpenAI y DeepSeek: solo aceptan la temperatura predeterminada. */
    public static function fixedTemperature(string $model): bool
    {
        return (bool) preg_match('/^(o\d|gpt-[5-9])|reasoner/i', $model);
    }

    /** Modelos que razonan antes de responder y gastan tokens en ello. */
    public static function thinks(string $model): bool
    {
        return self::fixedTemperature($model) || (bool) preg_match('/gemini-(2\.5|[3-9])|thinking|-r1/i', $model);
    }
}
