<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tutor_minute_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'tutor_daily_limit' => ['required', 'integer', 'min:1', 'max:5000'],
            'generation_minute_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'generation_daily_limit' => ['required', 'integer', 'min:1', 'max:5000'],
            'diagnostic_min_characters' => ['required', 'integer', 'min:1', 'max:1000', 'lte:diagnostic_max_characters'],
            'diagnostic_max_characters' => ['required', 'integer', 'min:10', 'max:5000'],
            'ai_token_budget' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }
}
