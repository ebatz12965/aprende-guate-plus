<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAiSettingsRequest;
use App\Models\PlatformSetting;
use App\Support\Audit;
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
        $before = PlatformSetting::aiLimits();
        $labels = [
            'tutor_minute_limit' => 'consultas al tutor por minuto',
            'tutor_daily_limit' => 'consultas al tutor por día',
            'generation_minute_limit' => 'generaciones por minuto',
            'generation_daily_limit' => 'generaciones por día',
            'diagnostic_min_characters' => 'mínimo de caracteres del diagnóstico',
            'diagnostic_max_characters' => 'máximo de caracteres del diagnóstico',
            'ai_token_budget' => 'presupuesto de tokens',
        ];
        $changes = [];

        foreach ($data as $key => $value) {
            if ((int) ($before[$key] ?? 0) !== (int) $value) {
                $changes[$labels[$key] ?? $key] = ['antes' => $before[$key] ?? '—', 'después' => $value];
            }
            PlatformSetting::setInteger($key, $value);
        }
        if ($changes) {
            Audit::log('configuracion', 'Cambió los límites de IA', null, $changes);
        }

        return back()->with('success', 'Los límites de IA y diagnóstico fueron actualizados.');
    }
}
