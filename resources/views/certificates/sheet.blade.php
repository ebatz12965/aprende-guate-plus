{{--
    Hoja del certificado (A4 horizontal, 297 × 210 mm). La misma plantilla sirve para el PDF (dompdf) y para la
    vista previa en el navegador, así que solo usa CSS que dompdf entiende: posiciones absolutas, tablas y mm.
--}}
@php
    $name = $certificate->recipient_name;
    // Los nombres y las rutas largas bajan de tamaño para caber en una o dos líneas sin chocar con la firma.
    $nameSize = match (true) {
        mb_strlen($name) > 42 => 24,
        mb_strlen($name) > 32 => 29,
        mb_strlen($name) > 24 => 34,
        default => 40,
    };
    $title = \Illuminate\Support\Str::limit($certificate->title, 95);
    $titleSize = match (true) {
        mb_strlen($title) > 70 => 15,
        mb_strlen($title) > 48 => 17,
        default => 21,
    };
    $showGoal = $certificate->goal && $certificate->goal !== $certificate->title && mb_strlen($title) <= 70;
    $duration = $certificate->hours ? $certificate->hours.' horas' : null;
@endphp
<style>
    @font-face { font-family: 'Figtree'; font-weight: 400; font-style: normal; src: url('{{ $fontBase }}/Figtree-400.ttf') format('truetype'); }
    @font-face { font-family: 'Figtree'; font-weight: 600; font-style: normal; src: url('{{ $fontBase }}/Figtree-600.ttf') format('truetype'); }
    @font-face { font-family: 'Figtree'; font-weight: 800; font-style: normal; src: url('{{ $fontBase }}/Figtree-800.ttf') format('truetype'); }
    @font-face { font-family: 'Great Vibes'; font-weight: 400; font-style: normal; src: url('{{ $fontBase }}/GreatVibes-Regular.ttf') format('truetype'); }

    .ag-cert { position: relative; width: 297mm; height: 210mm; overflow: hidden; background: #FFFFFF; color: #1E293B; font-family: 'Figtree', sans-serif; font-weight: 400; }
    .ag-cert * { box-sizing: border-box; margin: 0; padding: 0; }

    /* Banda nocturna: Cielo de Atitlán */
    .ag-cert .band { position: absolute; left: 0; top: 0; width: 92mm; height: 210mm; background: #020617; }
    .ag-cert .band .contours { position: absolute; left: 0; top: 0; width: 92mm; height: 210mm; }
    .ag-cert .band .logo { position: absolute; left: 14mm; top: 15mm; width: 33mm; }
    .ag-cert .band .tagline { position: absolute; left: 14mm; top: 52mm; width: 66mm; color: #81D4FA; font-size: 10pt; line-height: 14pt; font-weight: 600; }
    .ag-cert .band .verify { position: absolute; left: 14mm; bottom: 14mm; width: 66mm; }
    .ag-cert .band .label { color: #81D4FA; font-size: 6.5pt; line-height: 9pt; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; }
    .ag-cert .band .code { margin-top: 1mm; color: #FFFFFF; font-size: 13pt; line-height: 17pt; font-weight: 800; letter-spacing: 0.04em; }
    .ag-cert .band .qr-row { margin-top: 5mm; width: 66mm; border-collapse: collapse; }
    .ag-cert .band .qr-cell { width: 25mm; vertical-align: top; }
    .ag-cert .band .qr { width: 25mm; height: 25mm; background: #FFFFFF; border-radius: 2.5mm; padding: 2mm; }
    .ag-cert .band .qr img { width: 21mm; height: 21mm; display: block; }
    .ag-cert .band .qr-text { padding-left: 4mm; vertical-align: middle; color: #CBD5E1; font-size: 7.5pt; line-height: 11pt; }
    .ag-cert .band .qr-text strong { display: block; color: #FFFFFF; font-weight: 800; font-size: 8pt; }
    .ag-cert .band .qr-text span { color: #81D4FA; font-weight: 600; }

    /* Cuerpo */
    .ag-cert .main { position: absolute; left: 92mm; top: 0; width: 205mm; height: 210mm; }
    .ag-cert .heading { position: absolute; left: 22mm; top: 19mm; width: 160mm; color: #0F172A; font-size: 25pt; line-height: 30pt; font-weight: 800; letter-spacing: -0.02em; }
    .ag-cert .heading-rule { position: absolute; left: 22mm; top: 34mm; width: 18mm; height: 1.3mm; background: #2196F3; border-radius: 1mm; }
    .ag-cert .body { position: absolute; left: 22mm; top: 50mm; width: 165mm; }
    .ag-cert .lead { color: #475569; font-size: 12pt; line-height: 17pt; }
    .ag-cert .name { margin-top: 3mm; color: #0F172A; font-weight: 800; letter-spacing: -0.03em; }
    .ag-cert .route { margin-top: 2mm; color: #1565C0; font-weight: 800; letter-spacing: -0.015em; }
    .ag-cert .goal { margin-top: 2.5mm; color: #475569; font-size: 10pt; line-height: 14pt; }
    .ag-cert .facts { margin-top: 9mm; border-collapse: collapse; }
    .ag-cert .facts td { padding: 0 6mm 0 0; vertical-align: top; white-space: nowrap; }
    .ag-cert .facts td + td { padding-left: 6mm; border-left: 0.3mm solid #E2E8F0; }
    .ag-cert .facts .k { color: #64748B; font-size: 6.5pt; line-height: 9pt; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; }
    .ag-cert .facts .v { margin-top: 1.2mm; color: #0F172A; font-size: 11.5pt; line-height: 15pt; font-weight: 600; }

    /* Firma y sello */
    .ag-cert .signature { position: absolute; left: 22mm; bottom: 17mm; width: 74mm; }
    .ag-cert .signature .script { color: #0F172A; font-family: 'Great Vibes', cursive; font-size: 34pt; line-height: 30pt; height: 15mm; }
    .ag-cert .signature .line { height: 0.3mm; background: #334155; }
    .ag-cert .signature .who { margin-top: 2mm; color: #0F172A; font-size: 10.5pt; line-height: 14pt; font-weight: 800; }
    .ag-cert .signature .role { color: #475569; font-size: 9pt; line-height: 13pt; }
    .ag-cert .seal { position: absolute; right: 22mm; bottom: 16mm; width: 30mm; height: 30mm; }
</style>
<div class="ag-cert">
    <div class="band">
        <img class="contours" src="{{ $contours }}" alt="">
        <img class="logo" src="{{ $logo }}" alt="Aprende Guate">
        <p class="tagline">Habilidades para el trabajo, con rumbo claro.</p>
        <div class="verify">
            <p class="label">Certificado n.º</p>
            <p class="code">{{ $certificate->code }}</p>
            <table class="qr-row"><tr>
                <td class="qr-cell"><div class="qr"><img src="{{ $qr }}" alt="Código QR de verificación"></div></td>
                <td class="qr-text"><strong>Verifica este certificado</strong>Escanea el código o visita<br><span>{{ \Illuminate\Support\Str::beforeLast($verifyLabel, '/') }}/</span><br><span>{{ $certificate->code }}</span></td>
            </tr></table>
        </div>
    </div>

    <div class="main">
        <h1 class="heading">Certificado de finalización</h1>
        <div class="heading-rule"></div>

        <div class="body">
            <p class="lead">Aprende Guate certifica que</p>
            <p class="name" style="font-size: {{ $nameSize }}pt; line-height: {{ round($nameSize * 1.12) }}pt;">{{ $name }}</p>
            <p class="lead" style="margin-top: 5mm;">completó la ruta de aprendizaje</p>
            <p class="route" style="font-size: {{ $titleSize }}pt; line-height: {{ round($titleSize * 1.25) }}pt;">{{ $title }}</p>
            @if ($showGoal)
                <p class="goal">Meta: {{ \Illuminate\Support\Str::limit($certificate->goal, 120) }}</p>
            @endif

        <table class="facts"><tr>
            @if ($certificate->level)<td><p class="k">Nivel inicial</p><p class="v">{{ $certificate->level }}</p></td>@endif
            @if ($duration)<td><p class="k">Duración estimada</p><p class="v">{{ $duration }}</p></td>@endif
            <td><p class="k">Contenido</p><p class="v">{{ $certificate->phases }} {{ $certificate->phases === 1 ? 'fase' : 'fases' }} · {{ $certificate->topics }} {{ $certificate->topics === 1 ? 'tema' : 'temas' }}</p></td>
            <td><p class="k">Emitido en Guatemala</p><p class="v">{{ $issuedOn }}</p></td>
        </tr></table>
        </div>

        <div class="signature">
            <p class="script">{{ $certificate->signer_name }}</p>
            <div class="line"></div>
            <p class="who">{{ $certificate->signer_name }}</p>
            <p class="role">{{ $certificate->signer_title }}</p>
        </div>
        <img class="seal" src="{{ $seal }}" alt="">
    </div>
</div>
