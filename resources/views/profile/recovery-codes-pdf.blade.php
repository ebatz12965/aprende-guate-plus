<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Códigos de recuperación · Aprende Guate</title>
    @php($fontBase = public_path('fonts/certificate'))
    <style>
        @font-face { font-family: 'Figtree'; font-weight: 400; font-style: normal; src: url('{{ $fontBase }}/Figtree-400.ttf') format('truetype'); }
        @font-face { font-family: 'Figtree'; font-weight: 600; font-style: normal; src: url('{{ $fontBase }}/Figtree-600.ttf') format('truetype'); }
        @font-face { font-family: 'Figtree'; font-weight: 800; font-style: normal; src: url('{{ $fontBase }}/Figtree-800.ttf') format('truetype'); }
        @page { size: A4; margin: 22mm 20mm; }
        body { margin: 0; color: #1E293B; font-family: 'Figtree', sans-serif; font-size: 10.5pt; line-height: 1.5; }
        .brand { font-weight: 800; font-size: 12pt; color: #0F172A; }
        .brand img { width: 26px; height: 26px; vertical-align: middle; margin-right: 8px; }
        h1 { margin: 10mm 0 0; font-size: 22pt; font-weight: 800; color: #0F172A; line-height: 1.15; }
        .lead { margin: 8px 0 0; color: #475569; }
        .meta { margin-top: 14px; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 10px; background: #F8FAFC; color: #475569; font-size: 9.5pt; }
        .meta strong { color: #0F172A; }
        table.codes { width: 100%; margin-top: 18px; border-collapse: separate; border-spacing: 8px 6px; }
        table.codes td { width: 50%; padding: 12px 0; border: 1.5px solid #BBDEFB; border-radius: 10px; background: #E3F2FD; text-align: center; font-family: 'DejaVu Sans Mono', monospace; font-size: 16pt; font-weight: bold; letter-spacing: 2px; color: #0F172A; }
        table.codes td span { display: block; font-family: 'Figtree', sans-serif; font-size: 8pt; font-weight: 600; letter-spacing: 0; color: #1565C0; }
        h2 { margin: 20px 0 6px; font-size: 12pt; font-weight: 800; color: #0F172A; }
        ul { margin: 0; padding-left: 16px; color: #334155; }
        li { margin-bottom: 4px; }
        .warn { margin-top: 18px; padding: 10px 14px; border: 1px solid #FCD34D; border-radius: 10px; background: #FFFBEB; color: #92400E; font-size: 9.5pt; }
        .foot { margin-top: 10mm; color: #64748B; font-size: 8.5pt; border-top: 1px solid #E2E8F0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="brand"><img src="{{ public_path('images/aprende-guate-app-icon.png') }}" alt="">Aprende Guate</div>

    <h1>Códigos de recuperación</h1>
    <p class="lead">Úsalos para entrar a tu cuenta si pierdes acceso a tu aplicación autenticadora. Cada código sirve una sola vez.</p>

    <div class="meta">
        Cuenta: <strong>{{ $user->email }}</strong><br>
        Generados el {{ $generatedAt->timezone(config('app.timezone'))->translatedFormat('j \d\e F \d\e Y, H:i') }}
    </div>

    <table class="codes">
        @foreach (array_chunk($codes, 2) as $row => $pair)
            <tr>
                @foreach ($pair as $col => $code)
                    <td><span>Código {{ $row * 2 + $col + 1 }}</span>{{ $code }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <h2>Cómo usarlos</h2>
    <ul>
        <li>Al iniciar sesión, en «Confirma que eres tú», elige la pestaña <strong>Código de recuperación</strong>.</li>
        <li>Escribe uno de estos códigos. Después de usarlo, márcalo o táchalo en esta hoja.</li>
        <li>Si te quedan pocos, genera códigos nuevos en <strong>Configuración › Seguridad y privacidad</strong>.</li>
    </ul>

    <div class="warn"><strong>Guárdalos en un lugar seguro y privado.</strong> Quien tenga estos códigos y tu contraseña puede entrar a tu cuenta. Si generas códigos nuevos, estos dejan de servir.</div>

    <p class="foot">Aprende Guate · aprendeguate.com · Documento generado por la plataforma a solicitud del titular de la cuenta.</p>
</body>
</html>
