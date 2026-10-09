<?php

namespace App\Services\Learning;

use App\Models\Certificate;
use App\Models\LearningPath;
use App\Models\LearningPathTopic;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Emite y dibuja los certificados de las rutas completadas. */
class CertificateService
{
    /** Emite el certificado de una ruta completada; si ya existe, devuelve el mismo. */
    public function issue(LearningPath $path): Certificate
    {
        if ($existing = Certificate::where('learning_path_id', $path->id)->first()) {
            return $existing;
        }
        $path->loadMissing('user', 'items');

        return Certificate::create([
            'code' => $this->newCode(),
            'user_id' => $path->user_id,
            'learning_path_id' => $path->id,
            'recipient_name' => Str::of($path->user->name)->squish()->limit(120, '')->toString(),
            'title' => Str::limit($path->title, 250, ''),
            'goal' => $path->goal ? Str::limit($path->goal, 500, '') : null,
            'level' => $path->estimated_level,
            'hours' => $path->estimated_hours,
            'phases' => $path->items->count(),
            'topics' => LearningPathTopic::whereIn('learning_path_item_id', $path->items->pluck('id'))->count(),
            'signer_name' => (string) config('certificates.signer_name'),
            'signer_title' => (string) config('certificates.signer_title'),
            'issued_at' => $path->completed_at ?? now(),
        ]);
    }

    /** Emite los certificados que falten de rutas ya completadas (por ejemplo, las terminadas antes de existir los certificados). */
    public function issueMissing(?int $userId = null): int
    {
        $paths = LearningPath::where('status', 'completed')->doesntHave('certificate')->when($userId, fn ($query) => $query->where('user_id', $userId))->get();
        $paths->each(fn (LearningPath $path) => $this->issue($path));

        return $paths->count();
    }

    public function pdf(Certificate $certificate): Response
    {
        return Pdf::loadView('certificates.pdf', $this->viewData($certificate, true))
            ->setPaper('a4', 'landscape')
            ->setOption(['dpi' => 144, 'defaultFont' => 'Figtree', 'isFontSubsettingEnabled' => true])
            ->download($certificate->fileName());
    }

    /** @return array<string, mixed> */
    public function viewData(Certificate $certificate, bool $forPdf = false): array
    {
        $verifyUrl = route('certificates.verify', $certificate);

        return [
            'certificate' => $certificate,
            'forPdf' => $forPdf,
            // dompdf lee los archivos del disco; el navegador, por URL.
            'fontBase' => $forPdf ? public_path('fonts/certificate') : asset('fonts/certificate'),
            'logo' => $forPdf ? public_path('images/aprende-guate-logo-certificado.png') : asset('images/aprende-guate-logo-certificado.png'),
            'verifyUrl' => $verifyUrl,
            'verifyLabel' => preg_replace('#^https?://#', '', $verifyUrl),
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($this->qr($verifyUrl)),
            'contours' => 'data:image/svg+xml;base64,'.base64_encode($this->contours($certificate->code)),
            'seal' => 'data:image/svg+xml;base64,'.base64_encode($this->seal()),
            'issuedOn' => $certificate->issued_at->locale('es')->translatedFormat('j \d\e F \d\e Y'),
        ];
    }

    private function newCode(): string
    {
        // Sin letras que se confunden (0/O, 1/I/L) para que se pueda dictar o escribir a mano.
        do {
            $code = 'AG-'.now()->format('Y').'-'.collect(range(1, 2))->map(fn () => collect(range(1, 4))->map(fn () => '23456789ABCDEFGHJKMNPQRSTUVWXYZ'[random_int(0, 29)])->implode(''))->implode('-');
        } while (Certificate::where('code', $code)->exists());

        return $code;
    }

    private function qr(string $url): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(240, 0), new SvgImageBackEnd)))->writeString($url);

        // Fondo transparente y módulos en el color de la tinta del certificado.
        return str_replace(['fill="#000000"', 'fill="#ffffff"'], ['fill="#020617"', 'fill="none"'], $svg);
    }

    /**
     * Curvas de nivel de un lago (un guiño al Atitlán) para la banda nocturna. La forma sale del código del
     * certificado: cada certificado tiene su propio trazo, siempre igual al volver a descargarlo.
     */
    private function contours(string $code): string
    {
        $seed = crc32($code);
        $rand = function () use (&$seed): float {
            $seed = ($seed * 1103515245 + 12345) & 0x7FFFFFFF;

            return $seed / 0x7FFFFFFF;
        };
        $phases = [$rand() * 6.28, $rand() * 6.28, $rand() * 6.28];
        [$cx, $cy] = [130 + $rand() * 40, 330 + $rand() * 60];
        $paths = '';
        for ($ring = 0; $ring < 11; $ring++) {
            $radius = 34 + $ring * 27;
            // dompdf no recorta las imágenes: cada curva se parte en tramos que quedan dentro de la banda.
            $runs = [[]];
            for ($step = 0; $step <= 144; $step++) {
                $angle = $step / 144 * 2 * M_PI;
                $wobble = 1 + 0.16 * sin(2 * $angle + $phases[0] + $ring * 0.25) + 0.09 * sin(3 * $angle + $phases[1]) + 0.05 * sin(5 * $angle + $phases[2] - $ring * 0.4);
                [$x, $y] = [$cx + cos($angle) * $radius * $wobble * 1.15, $cy + sin($angle) * $radius * $wobble * 0.85];
                if ($x < 1 || $x > 347 || $y < 1 || $y > 793) {
                    $runs[] = [];

                    continue;
                }
                $runs[count($runs) - 1][] = round($x, 1).','.round($y, 1);
            }
            $opacity = round(0.5 - $ring * 0.035, 3);
            $width = $ring === 2 ? 1.6 : 0.9;
            foreach (array_filter($runs, fn (array $run) => count($run) > 1) as $run) {
                $paths .= '<polyline points="'.implode(' ', $run).'" fill="none" stroke="#2196F3" stroke-width="'.$width.'" stroke-opacity="'.$opacity.'"/>';
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="348" height="794" viewBox="0 0 348 794">'.$paths.'</svg>';
    }

    /** Sello con rosa de los vientos: la estrella del norte de «Rumbo claro». */
    private function seal(): string
    {
        $c = 60;
        $rays = '';
        foreach (range(0, 15) as $i) {
            $a = $i / 16 * 2 * M_PI;
            [$inner, $outer] = $i % 2 ? [44, 49] : [42, 51];
            $rays .= '<line x1="'.round($c + cos($a) * $inner, 2).'" y1="'.round($c + sin($a) * $inner, 2).'" x2="'.round($c + cos($a) * $outer, 2).'" y2="'.round($c + sin($a) * $outer, 2).'" stroke="#1565C0" stroke-width="1.4"/>';
        }
        $star = '';
        foreach ([0, 90, 180, 270] as $deg) {
            $a = deg2rad($deg - 90);
            $l = deg2rad($deg - 90 - 90);
            $r = deg2rad($deg - 90 + 90);
            $tip = [$c + cos($a) * 34, $c + sin($a) * 34];
            $left = [$c + cos($l) * 7, $c + sin($l) * 7];
            $right = [$c + cos($r) * 7, $c + sin($r) * 7];
            $fill = $deg === 0 ? '#2196F3' : '#1565C0';
            $star .= '<polygon points="'.round($tip[0], 2).','.round($tip[1], 2).' '.round($left[0], 2).','.round($left[1], 2).' '.$c.','.$c.' '.round($right[0], 2).','.round($right[1], 2).'" fill="'.$fill.'"/>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">'
            .'<circle cx="60" cy="60" r="57" fill="#E3F2FD"/><circle cx="60" cy="60" r="57" fill="none" stroke="#1565C0" stroke-width="2"/>'
            .'<circle cx="60" cy="60" r="38" fill="none" stroke="#1565C0" stroke-width="0.8" stroke-opacity="0.6"/>'
            .$rays.$star.'<circle cx="60" cy="60" r="4" fill="#FFFFFF" stroke="#1565C0" stroke-width="1.5"/></svg>';
    }
}
