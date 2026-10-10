<?php

namespace App\Http\Requests\Admin;

use App\AI\AgentCatalog;
use App\AI\ProviderRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAiAgentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'provider' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('ai.providers', [])))],
            // Identificadores de modelo del proveedor: letras, números, punto, guion, dos puntos y barra.
            'model' => ['nullable', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:\/-]+$/'],
            'max_tokens' => ['nullable', 'integer', 'min:50', 'max:32000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $agent = (string) $this->route('agent');
            if (! in_array($agent, AgentCatalog::AGENTS, true)) {
                abort(404);
            }
            if ($this->boolean('enabled') && config("ai.agents.{$agent}.planned")) {
                $validator->errors()->add('enabled', 'Este agente aún no tiene un módulo que lo use; no se puede activar.');
            }
            $provider = (string) $this->input('provider');
            if ($provider !== '' && ! app(ProviderRegistry::class)->configured($provider)) {
                $validator->errors()->add('provider', 'Ese proveedor no tiene API key. Agrega '.config("ai.providers.{$provider}.key_env").' en el archivo .env.');
            }
            // El agente de voz usa la API Realtime: solo OpenAI, un modelo realtime y como máximo 4096 tokens por respuesta.
            if (config("ai.agents.{$agent}.task") === 'realtime') {
                if ($provider !== '' && $provider !== 'openai') {
                    $validator->errors()->add('provider', 'La entrevista por voz solo funciona con OpenAI.');
                }
                if (filled($this->input('model')) && ! str_contains((string) $this->input('model'), 'realtime')) {
                    $validator->errors()->add('model', 'Elige un modelo de voz en tiempo real (por ejemplo, gpt-realtime-2.1-mini).');
                }
                if ((int) $this->input('max_tokens') > 4096) {
                    $validator->errors()->add('max_tokens', 'El modelo de voz acepta como máximo 4096 tokens por respuesta.');
                }
            }
            $defaultProvider = config("ai.agents.{$agent}.provider") ?? config('ai.default_provider');
            if ($provider !== '' && $provider !== $defaultProvider && blank($this->input('model'))) {
                $validator->errors()->add('model', 'Elige un modelo del proveedor seleccionado.');
            }
        }];
    }
}
