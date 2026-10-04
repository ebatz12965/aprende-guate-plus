<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class SubmitChallengeEvidenceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'answer' => ['nullable', 'string', 'max:12000', 'required_without:attachment'],
            'methodology' => ['required', 'string', 'max:6000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,txt,csv,xlsx,xls,doc,docx,ppt,pptx,png,jpg,jpeg,webp,zip'],
        ];
    }
}
