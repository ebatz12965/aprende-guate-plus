@extends('layouts.admin')

@section('content')
@php
    $activa = $settings['enabled'];
    $bloques = collect($settings['slots'])->filter(fn ($slot) => filled($slot))->count();
@endphp
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Publicidad</h1>
        <p class="mt-2 text-slate-600">Espacios preparados para Google AdSense, por si la plataforma sigue siendo gratuita y se financia con anuncios. Mientras la publicidad esté apagada, no se muestra nada ni se carga ningún script de Google.</p>
    </div>

    <div @class([
        'mb-6 rounded-xl border p-4 text-sm leading-6',
        'border-emerald-200 bg-emerald-50 text-emerald-900' => $activa && $bloques > 0,
        'border-amber-200 bg-amber-50 text-amber-900' => $activa && $bloques === 0,
        'border-slate-200 bg-white text-slate-700' => ! $activa,
    ])>
        @if (! $activa)
            <strong>Estado: apagada.</strong> Ningún visitante ve anuncios.
        @elseif ($bloques === 0)
            <strong>Estado: activada, sin bloques.</strong> Agrega el ID de al menos un bloque para que aparezca un anuncio.
        @else
            <strong>Estado: activada.</strong> {{ $bloques }} {{ $bloques === 1 ? 'lugar configurado' : 'lugares configurados' }}. <a href="{{ route('ads.txt') }}" target="_blank" rel="noopener" class="font-bold underline">Ver ads.txt</a>
        @endif
    </div>

    <form action="{{ route('admin.ads-settings.update') }}" method="POST" class="space-y-6">
        @csrf @method('PUT')
        <section class="platform-feature">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $activa)) class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span>
                    <span class="font-bold text-slate-900">Mostrar publicidad</span>
                    <span class="mt-1 block text-sm text-slate-500">Actívala solo cuando Google haya aprobado el sitio en AdSense.</span>
                </span>
            </label>
            <label class="mt-5 block text-sm font-bold text-slate-700">ID de editor de AdSense
                <input name="client" value="{{ old('client', $settings['client']) }}" autocomplete="off" spellcheck="false" class="mt-2 block w-full rounded-xl border-slate-200 font-mono" placeholder="ca-pub-0000000000000000">
                <span class="mt-1 block text-xs font-normal text-slate-500">Está en AdSense, en Cuenta → Información de la cuenta. Con él se genera <code>/ads.txt</code> automáticamente.</span>
            </label>
            @error('client')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </section>

        <section class="platform-feature">
            <h2 class="!mt-0 text-xl">Lugares de los anuncios</h2>
            <p class="mt-1 text-sm text-slate-500">Escribe el ID del bloque de anuncios (10 dígitos) de cada lugar. Un lugar vacío no muestra nada. Por diseño no hay anuncios en lecciones, prácticas, entrevistas ni evaluaciones.</p>
            <div class="mt-5 space-y-5">
                @foreach ($placements as $lugar => [$nombre, $ubicacion])
                    <label class="block text-sm font-bold text-slate-700">{{ $nombre }}
                        <span class="block text-xs font-normal text-slate-500">{{ $ubicacion }}</span>
                        <input name="slot_{{ $lugar }}" value="{{ old('slot_'.$lugar, $settings['slots'][$lugar] ?? '') }}" inputmode="numeric" autocomplete="off" class="mt-2 block w-full rounded-xl border-slate-200 font-mono" placeholder="0000000000">
                    </label>
                    @error('slot_'.$lugar)<p class="-mt-3 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                @endforeach
            </div>
        </section>

        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
            <strong>Antes de activarla:</strong> crea la cuenta de AdSense con el dominio aprendeguate.com, espera la aprobación de Google y crea un bloque de anuncios «display» para cada lugar. Al activarla, la política de cookies informa automáticamente sobre las cookies de publicidad.
        </div>
        <div class="flex justify-end"><button class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white shadow-md shadow-blue-600/20 hover:bg-blue-700">Guardar publicidad</button></div>
    </form>
</div>
@endsection
