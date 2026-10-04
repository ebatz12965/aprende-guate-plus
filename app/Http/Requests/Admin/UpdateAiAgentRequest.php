<?php

namespace App\Http\Requests\Admin;

use App\AI\AgentCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAiAgentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            // Identificadores de modelo del proveedor: letras, números, punto, guion, dos puntos y barra.
            'model' => ['nullable', 'string', 'max:160', 'regex:/^[A-Za-z0-9._:\/-]+$/'],
            'max_tokens' => ['nullable', 'integer', 'min:50', 'max:8000'],
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
        }];
    }
}
