<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAiSettingsRequest;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.ai-settings.edit', ['settings' => PlatformSetting::aiLimits()]);
    }

    public function update(UpdateAiSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            PlatformSetting::setInteger($key, $value);
        }

        return back()->with('success', 'Los límites de IA y diagnóstico fueron actualizados.');
    }
}
