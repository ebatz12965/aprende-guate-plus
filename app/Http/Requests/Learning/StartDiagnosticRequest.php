<?php

namespace App\Http\Requests\Learning;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Http\FormRequest;

class StartDiagnosticRequest extends FormRequest
{
    public function rules(): array
    {
        $limits = PlatformSetting::aiLimits();

        return [
            'primary_answer' => ['required', 'string', 'min:'.$limits['diagnostic_min_characters'], 'max:'.$limits['diagnostic_max_characters']],
        ];
    }
}
