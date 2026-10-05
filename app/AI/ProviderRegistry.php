<?php

namespace App\AI;

use App\AI\Contracts\AiProvider;
use App\AI\Providers\AnthropicProvider;
use App\AI\Providers\OpenAiCompatibleProvider;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

/** Proveedores de IA configurados en config/ai.php y los modelos que ofrece cada uno. */
class ProviderRegistry
{
    /** @return array<string, array{name: string, configured: bool, key_env: ?string, keys_url: ?string}> */
    public function all(): array
    {
        return collect(config('ai.providers', []))->map(fn (array $provider, string $slug) => [
            'name' => $provider['name'] ?? $slug,
            'configured' => $this->configured($slug),
            'key_env' => $provider['key_env'] ?? null,
            'keys_url' => $provider['keys_url'] ?? null,
        ])->all();
    }

    public function exists(string $slug): bool
    {
        return is_array(config("ai.providers.{$slug}"));
    }

    /** Tiene API key y está habilitado. La clave de OpenAI acepta también la variable antigua AI_API_KEY. */
    public function configured(string $slug): bool
    {
        return $this->exists($slug) && (bool) config("ai.providers.{$slug}.enabled", true) && filled($this->key($slug));
    }

    public function client(string $slug): AiProvider
    {
        if (! $this->exists($slug)) {
            throw new RuntimeException("El proveedor {$slug} no está configurado.");
        }
        $config = array_merge(config("ai.providers.{$slug}"), ['key' => $this->key($slug), 'base_url' => config("ai.providers.{$slug}.base_url") ?: config('ai.base_url')]);

        return ($config['driver'] ?? 'openai') === 'anthropic' ? new AnthropicProvider($config) : new OpenAiCompatibleProvider($config);
    }

    /**
     * Modelos de texto que ofrece el proveedor, consultados a su API y guardados un tiempo.
     *
     * @return array<int, string>
     */
    public function models(string $slug, bool $refresh = false): array
    {
        if (! $this->configured($slug)) {
            return [];
        }
        $key = "ai.models.{$slug}";
        if ($refresh) {
            Cache::forget($key);
        }

        if (is_array($cached = Cache::get($key))) {
            return $cached;
        }
        try {
            $client = $this->client($slug);
            $models = method_exists($client, 'models') ? $client->models() : [];
        } catch (Throwable) {
            $models = [];
        }

        // Solo modelos de chat: fuera audio, imágenes, embeddings, moderación y similares.
        $models = collect($models)
            ->reject(fn ($id) => preg_match('/embed|whisper|tts|audio|dall-e|image|moderation|transcribe|realtime|search|computer-use|davinci|babbage|aqa|veo|imagen|learnlm|sora|codex|instruct|gpt-live/i', $id))
            // Los modelos «pro» de OpenAI solo funcionan con su otra API (Responses).
            ->reject(fn ($id) => preg_match('/^(gpt-[\d.]+|o\d)-pro$/i', $id))
            // Las versiones con fecha repiten el mismo modelo: basta con el nombre sin fecha.
            ->reject(fn ($id) => preg_match('/-\d{4}-\d{2}-\d{2}$|-\d{4}$/', $id))
            ->unique()->sort()->values()->all();
        // Una lista vacía suele ser un fallo de red: no se guarda para volver a intentar.
        if ($models) {
            Cache::put($key, $models, now()->addMinutes((int) config('ai.models_cache_minutes', 360)));
        }

        return $models;
    }

    private function key(string $slug): ?string
    {
        $key = config("ai.providers.{$slug}.key");

        return $slug === 'openai' ? ($key ?: config('ai.key')) : $key;
    }
}
