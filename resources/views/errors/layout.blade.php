{{--
    Base de las páginas de error. Es autónoma a propósito: estilos propios, sin Vite, sin consultas a la base de
    datos ni datos de la sesión, para que se vea bien incluso cuando lo que falló es el servidor o la base de datos.
    Variables: $code, $title, $message, $hint (opcional), $actions (lista de [texto, url, principal]), $reference (opcional).
--}}
@php
    $logo = 'data:image/png;base64,'.base64_encode(@file_get_contents(public_path('images/aprende-guate-app-icon.png')) ?: '');
    $home = url('/');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Aprende Guate</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <script nonce="{{ Vite::cspNonce() }}">try { if (localStorage.getItem('platform-theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}</script>
    <style>
        :root { --ground: #F8FAFC; --surface: #FFFFFF; --ink: #0F172A; --text: #334155; --muted: #64748B; --border: #E2E8F0; --blue: #1976D2; --blue-ink: #1565C0; --tint: #E3F2FD; }
        .dark { --ground: #020617; --surface: #0F172A; --ink: #F8FAFC; --text: #CBD5E1; --muted: #94A3B8; --border: #334155; --blue: #2196F3; --blue-ink: #81D4FA; --tint: rgb(25 118 210 / .2); }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body { min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: var(--ground); color: var(--text);
            font: 400 16px/1.6 Figtree, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; -webkit-font-smoothing: antialiased; }
        ::selection { background: var(--tint); color: var(--ink); }
        main { width: min(34rem, 100%); }
        .brand { display: inline-flex; align-items: center; gap: .75rem; color: var(--ink); text-decoration: none; font-weight: 800; font-size: 1.05rem; }
        .brand img { width: 2.5rem; height: 2.5rem; border-radius: .75rem; }
        .card { margin-top: 1.75rem; padding: 2rem; border: 1px solid var(--border); border-radius: 1.5rem; background: var(--surface); box-shadow: 0 18px 40px -24px rgb(2 6 23 / .35); }
        .code { display: inline-block; padding: .25rem .75rem; border-radius: 999px; background: var(--tint); color: var(--blue-ink); font-weight: 800; font-size: .8rem; letter-spacing: .12em; }
        h1 { margin: 1rem 0 0; color: var(--ink); font-size: clamp(1.6rem, 4vw, 2.1rem); line-height: 1.15; font-weight: 800; letter-spacing: -.02em; text-wrap: balance; }
        p { margin: .75rem 0 0; }
        .hint { color: var(--muted); font-size: .92rem; }
        .ref { margin-top: 1.25rem; padding: .75rem 1rem; border-radius: .75rem; background: var(--ground); border: 1px dashed var(--border); font-size: .9rem; }
        .ref strong { color: var(--ink); font-variant-numeric: tabular-nums; letter-spacing: .08em; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
        .btn { display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0 1.25rem; border-radius: .75rem; font-weight: 700; font-size: .9rem; text-decoration: none; border: 1px solid var(--border); color: var(--ink); background: var(--surface); cursor: pointer; }
        .btn:hover { border-color: var(--blue); }
        .btn-primary { background: var(--blue); border-color: var(--blue); color: #fff; }
        .dark .btn-primary { color: #020617; }
        .btn-primary:hover { filter: brightness(1.08); }
        .btn:focus-visible { outline: 3px solid var(--blue); outline-offset: 2px; }
        footer { margin-top: 1.25rem; color: var(--muted); font-size: .8rem; text-align: center; }
    </style>
</head>
<body>
    <main>
        <a class="brand" href="{{ $home }}"><img src="{{ $logo }}" alt="">Aprende Guate</a>
        <section class="card" aria-labelledby="error-title">
            <span class="code">ERROR {{ $code }}</span>
            <h1 id="error-title">{{ $title }}</h1>
            <p>{{ $message }}</p>
            @if (! empty($hint))<p class="hint">{{ $hint }}</p>@endif
            @if (! empty($reference))
                <p class="ref">Código del error: <strong>{{ $reference }}</strong><br><span class="hint">Si se repite, compártelo con soporte para que lo encontremos rápido.</span></p>
            @endif
            <div class="actions">
                @foreach ($actions ?? [[ 'Ir al inicio', $home, true ]] as [$label, $url, $primary])
                    @if ($url === 'back')
                        <button type="button" class="btn {{ $primary ? 'btn-primary' : '' }}" data-error-action="back" data-home="{{ $home }}">{{ $label }}</button>
                    @elseif ($url === 'reload')
                        <button type="button" class="btn {{ $primary ? 'btn-primary' : '' }}" data-error-action="reload">{{ $label }}</button>
                    @else
                        <a class="btn {{ $primary ? 'btn-primary' : '' }}" href="{{ $url }}">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
        </section>
        <footer>Plataforma educativa de Guatemala</footer>
    </main>
    <script nonce="{{ Vite::cspNonce() }}">
        document.querySelectorAll('[data-error-action]').forEach((button) => button.addEventListener('click', () => {
            if (button.dataset.errorAction === 'reload') return location.reload();
            history.length > 1 ? history.back() : location.assign(button.dataset.home);
        }));
    </script>
</body>
</html>
