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
            $defaultProvider = config("ai.agents.{$agent}.provider") ?? config('ai.default_provider');
            if ($provider !== '' && $provider !== $defaultProvider && blank($this->input('model'))) {
                $validator->errors()->add('model', 'Elige un modelo del proveedor seleccionado.');
            }
        }];
    }
}
