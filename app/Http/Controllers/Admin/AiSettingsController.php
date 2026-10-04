<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.ai-settings.edit', ['settings' => PlatformSetting::aiLimits()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tutor_minute_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'tutor_daily_limit' => ['required', 'integer', 'min:1', 'max:5000'],
            'generation_minute_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'generation_daily_limit' => ['required', 'integer', 'min:1', 'max:5000'],
            'diagnostic_min_characters' => ['required', 'integer', 'min:1', 'max:1000', 'lte:diagnostic_max_characters'],
            'diagnostic_max_characters' => ['required', 'integer', 'min:10', 'max:5000'],
            'ai_token_budget' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ]);

        foreach ($data as $key => $value) {
            PlatformSetting::setInteger($key, $value);
        }

        return back()->with('success', 'Los límites de IA y diagnóstico fueron actualizados.');
    }
}
