<?php

namespace App\Services\Library;

use App\Models\AiRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Narración de los extractos animados con ElevenLabs (texto a voz). Cada paso se convierte en un MP3 una sola vez
 * y queda guardado en el disco privado; ElevenLabs cobra por carácter, así que nunca se vuelve a pedir el mismo audio.
 */
class ElevenLabsNarrator
{
    public function configured(): bool
    {
        return filled(config('services.elevenlabs.key'));
    }

    /**
     * Genera el audio de cada paso. Devuelve las rutas (o null en los pasos que fallaron) y los caracteres enviados.
     *
     * @param  list<string>  $texts
     * @param  list<int>|null  $only  índices a narrar (los demás se omiten y quedan en null); null = todos
     * @return array{files: list<?string>, characters: int, voice: string, model: string}
     */
    public function narrate(string $directory, array $texts, ?array $only = null): array
    {
        $voice = (string) config('services.elevenlabs.voice_id');
        $model = (string) config('services.elevenlabs.model');
        $files = [];
        $characters = 0;
        $request = AiRequest::create(['request_uuid' => (string) Str::uuid(), 'agent_slug' => 'tutorial_script', 'provider' => 'elevenlabs', 'model' => $model,
            'operation' => 'walkthrough_narration', 'input_hash' => hash('sha256', implode("\n", $texts)), 'status' => 'processing']);
        $started = microtime(true);
        $failures = [];

        foreach ($texts as $index => $text) {
            if ($only !== null && ! in_array($index, $only, true)) {
                $files[] = null;

                continue;
            }
            try {
                $response = Http::withHeaders(['xi-api-key' => (string) config('services.elevenlabs.key'), 'Accept' => 'audio/mpeg'])
                    ->timeout((int) config('services.elevenlabs.timeout', 30))
                    // Los cortes de conexión (por ejemplo, un certificado interceptado un instante) se reintentan; los errores de la API no.
                    ->retry(3, 800, fn (Throwable $exception) => $exception instanceof ConnectionException, throw: false)
                    ->post("https://api.elevenlabs.io/v1/text-to-speech/{$voice}?output_format=mp3_44100_64", array_filter([
                        'text' => $text,
                        'model_id' => $model,
                        'language_code' => str_contains($model, 'multilingual_v2') ? null : 'es',
                        // El texto vecino ayuda a que la entonación entre pasos suene continua.
                        'previous_text' => $texts[$index - 1] ?? null,
                        'next_text' => $texts[$index + 1] ?? null,
                        'voice_settings' => ['stability' => 0.5, 'similarity_boost' => 0.75, 'speed' => 0.95],
                    ], fn ($value) => $value !== null));
                if (! $response->successful() || ! str_contains((string) $response->header('Content-Type'), 'audio')) {
                    $failures[] = 'Paso '.($index + 1).': HTTP '.$response->status();
                    $files[] = null;

                    continue;
                }
                $path = "{$directory}/".($index + 1).'.mp3';
                Storage::disk('local')->put($path, $response->body());
                $files[] = $path;
                $characters += mb_strlen($text);
            } catch (Throwable $exception) {
                $failures[] = 'Paso '.($index + 1).': '.Str::limit($exception->getMessage(), 120);
                $files[] = null;
            }
        }

        $request->update(['status' => $failures && ! array_filter($files) ? 'failed' : 'completed', 'failure_reason' => $failures ? Str::limit(implode(' · ', $failures), 500) : null,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000), 'completed_at' => now()]);

        return ['files' => $files, 'characters' => $characters, 'voice' => $voice, 'model' => $model];
    }
}
