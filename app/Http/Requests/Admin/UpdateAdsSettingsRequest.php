<?php

namespace App\Http\Requests\Admin;

use App\Support\Ads;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdsSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'enabled' => ['nullable', 'boolean'],
            'client' => ['required_if:enabled,1', 'nullable', 'string', 'regex:'.Ads::CLIENT_PATTERN],
        ];
        foreach (array_keys(Ads::PLACEMENTS) as $place) {
            $rules['slot_'.$place] = ['nullable', 'string', 'regex:'.Ads::SLOT_PATTERN];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'client.required_if' => 'Para activar la publicidad escribe tu ID de editor de AdSense.',
            'client.regex' => 'El ID de editor tiene la forma ca-pub- seguido de 16 dígitos.',
            'slot_*.regex' => 'El ID del bloque de anuncios tiene 10 dígitos.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->only(['client', ...array_map(fn ($p) => 'slot_'.$p, array_keys(Ads::PLACEMENTS))]))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)->all());
    }
}
