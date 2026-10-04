<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCareerProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'professional_goal' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'bio_visibility' => ['required', 'in:public,private'],
            'education' => ['nullable', 'array', 'max:10'],
            'education.*.institution' => ['nullable', 'string', 'max:160'],
            'education.*.program' => ['nullable', 'string', 'max:160'],
            'education.*.period' => ['nullable', 'string', 'max:80'],
            'education_visibility' => ['required', 'in:public,private'],
            'work_experience' => ['nullable', 'array', 'max:10'],
            'work_experience.*.role' => ['nullable', 'string', 'max:160'],
            'work_experience.*.company' => ['nullable', 'string', 'max:160'],
            'work_experience.*.period' => ['nullable', 'string', 'max:80'],
            'work_experience.*.description' => ['nullable', 'string', 'max:800'],
            'work_experience_visibility' => ['required', 'in:public,private'],
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    /** Datos del perfil sin archivos y sin filas vacías de educación o experiencia. */
    public function profileData(): array
    {
        $data = $this->safe()->except(['cv', 'profile_photo', 'remove_photo']);

        foreach (['education', 'work_experience'] as $section) {
            $data[$section] = collect($data[$section] ?? [])
                ->filter(fn (array $entry) => collect($entry)->filter()->isNotEmpty())
                ->values()
                ->all();
        }

        return $data;
    }
}
