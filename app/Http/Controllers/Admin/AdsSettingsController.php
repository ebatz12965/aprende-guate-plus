<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdsSettingsRequest;
use App\Models\PlatformSetting;
use App\Support\Ads;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Espacios publicitarios (RF-16): se dejan preparados y apagados hasta que la plataforma decida monetizar. */
class AdsSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.ads-settings.edit', ['settings' => PlatformSetting::adsSettings(), 'placements' => Ads::PLACEMENTS]);
    }

    public function update(UpdateAdsSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $enabled = $request->boolean('enabled');

        PlatformSetting::setString('ads_enabled', $enabled ? '1' : '0');
        PlatformSetting::setString('ads_client', (string) ($data['client'] ?? ''));
        foreach (array_keys(Ads::PLACEMENTS) as $place) {
            PlatformSetting::setString('ads_slot_'.$place, (string) ($data['slot_'.$place] ?? ''));
        }

        $configured = collect(array_keys(Ads::PLACEMENTS))->filter(fn ($place) => filled($data['slot_'.$place] ?? null))->values()->all();
        Audit::log('configuracion', $enabled ? 'Activó los espacios publicitarios' : 'Actualizó los espacios publicitarios (apagados)', null, ['activo' => $enabled, 'lugares' => $configured]);

        return back()->with('success', $enabled
            ? 'Publicidad activada. Los anuncios aparecen cuando Google aprueba el sitio y los bloques.'
            : 'Configuración guardada. La publicidad sigue apagada y no se muestra nada.');
    }

    /** /ads.txt: autoriza a Google a vender anuncios del sitio. Solo existe con la publicidad activada. */
    public function adsTxt(): Response
    {
        $line = Ads::adsTxt();
        abort_if($line === null, 404);

        return response($line."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
