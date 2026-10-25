{{-- Espacio publicitario (RF-16). No muestra nada mientras la publicidad esté apagada o el lugar no tenga bloque. --}}
@props(['lugar'])
@php($anuncio = \App\Support\Ads::slot($lugar))
@if ($anuncio)
    <aside {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900']) }} aria-label="Publicidad" data-espacio-publicitario="{{ $lugar }}">
        <p class="mb-2 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Publicidad</p>
        <ins class="adsbygoogle" style="display:block" data-ad-client="{{ $anuncio['client'] }}" data-ad-slot="{{ $anuncio['slot'] }}" data-ad-format="auto" data-full-width-responsive="true"></ins>
        @once
            <script async nonce="{{ Vite::cspNonce() }}" src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $anuncio['client'] }}" crossorigin="anonymous"></script>
        @endonce
        <script nonce="{{ Vite::cspNonce() }}">(window.adsbygoogle = window.adsbygoogle || []).push({});</script>
    </aside>
@endif
