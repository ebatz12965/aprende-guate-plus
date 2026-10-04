<?php

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMentorSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:160'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'about' => ['nullable', 'string', 'max:1500'],
            'acceptance_message' => ['required', 'string', 'max:2000'],
            'rejection_message' => ['required', 'string', 'max:2000'],
            'session_duration_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'availability' => ['nullable', 'array'],
        ];
    }
}
