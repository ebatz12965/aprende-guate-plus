<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'host' => ['required_if:enabled,1', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:enabled,1', 'nullable', 'integer', 'between:1,65535'],
            'encryption' => ['required', 'in:none,tls,ssl'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'from_address' => ['required_if:enabled,1', 'nullable', 'email:rfc', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
