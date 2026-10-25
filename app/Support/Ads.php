<?php

namespace App\Support;

use App\Models\PlatformSetting;

/**
 * Espacios publicitarios preparados para Google AdSense (RF-16).
 *
 * Están apagados hasta que el super administrador los activa con su ID de editor. Solo existen en lugares que no
 * interrumpen el aprendizaje: nunca en lecciones, prácticas, entrevistas ni evaluaciones. Cuando una página muestra
 * un anuncio, SecurityHeaders permite en el CSP los dominios de AdSense solo para esa respuesta.
 */
final class Ads
{
    /** Atributo de la petición que indica que la respuesta incluye un anuncio. */
    public const REQUEST_FLAG = 'ads.rendered';

    /** @var array<string, array{0: string, 1: string}> lugar => [nombre, ubicación] */
    public const PLACEMENTS = [
        'inicio' => ['Portada pública', 'Debajo de las funciones de la página principal.'],
        'panel' => ['Panel del estudiante', 'Al final de «Mi panel», después de las tarjetas de mentoría y Tutor IA.'],
    ];

    /** Dominios de scripts que AdSense necesita (se agregan al CSP solo en páginas con anuncio). */
    public const SCRIPT_HOSTS = [
        'https://pagead2.googlesyndication.com',
        'https://*.googlesyndication.com',
        'https://*.g.doubleclick.net',
        'https://www.googletagservices.com',
        'https://adservice.google.com',
        'https://*.adtrafficquality.google',
        'https://fundingchoicesmessages.google.com',
    ];

    public const CLIENT_PATTERN = '/^ca-pub-\d{16}$/';

    public const SLOT_PATTERN = '/^\d{10}$/';

    /**
     * Datos del anuncio de un lugar, o null si la publicidad está apagada o el lugar no está configurado.
     *
     * @return array{client: string, slot: string}|null
     */
    public static function slot(string $placement): ?array
    {
        if (! isset(self::PLACEMENTS[$placement])) {
            return null;
        }
        $settings = PlatformSetting::adsSettings();
        $slot = $settings['slots'][$placement] ?? '';
        if (! $settings['enabled'] || ! preg_match(self::CLIENT_PATTERN, $settings['client']) || ! preg_match(self::SLOT_PATTERN, $slot)) {
            return null;
        }
        request()->attributes->set(self::REQUEST_FLAG, true);

        return ['client' => $settings['client'], 'slot' => $slot];
    }

    /** Línea de /ads.txt que autoriza a Google a vender anuncios del sitio, o null si no aplica. */
    public static function adsTxt(): ?string
    {
        $settings = PlatformSetting::adsSettings();
        if (! $settings['enabled'] || ! preg_match(self::CLIENT_PATTERN, $settings['client'])) {
            return null;
        }

        return 'google.com, '.substr($settings['client'], 3).', DIRECT, f08c47fec0942fa0';
    }
}
