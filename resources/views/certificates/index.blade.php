@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-6xl">
    <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl sm:px-9">
        <div class="relative max-w-3xl">
            <h1 class="text-3xl font-extrabold">Mis certificados</h1>
            <p class="mt-2 leading-7 text-slate-300">Cada ruta de aprendizaje que completas te da un certificado de Aprende Guate. Descárgalo en PDF y comparte su enlace de verificación con quien quiera confirmarlo.</p>
        </div>
    </section>

    @if ($paths->isEmpty())
        <section class="mt-6 learning-panel text-center sm:py-12">
            <h2 class="text-xl font-extrabold text-slate-900">Todavía no completas una ruta</h2>
            @if ($activePath)
                @php($total = max(1, $activePath->items()->count()))
                @php($done = $activePath->items()->where('status', 'completed')->count())
                <p class="mx-auto mt-2 max-w-xl leading-7 text-slate-600">Vas en {{ $done }} de {{ $total }} fases de «{{ $activePath->title }}». Al aprobar la última fase recibirás aquí tu certificado.</p>
                <div class="mx-auto mt-5 h-2 max-w-sm rounded-full bg-slate-100" role="progressbar" aria-label="Avance de tu ruta" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $done }}"><div class="h-full rounded-full bg-blue-600" style="width: {{ round($done / $total * 100) }}%"></div></div>
                <a href="{{ route('adaptive.path') }}" class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-5 text-sm font-bold text-white hover:bg-blue-700">Continuar mi ruta</a>
            @else
                <p class="mx-auto mt-2 max-w-xl leading-7 text-slate-600">Haz tu diagnóstico para recibir una ruta personal. Cuando la completes, tu certificado aparecerá aquí.</p>
                <a href="{{ route('adaptive.diagnostic') }}" class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-5 text-sm font-bold text-white hover:bg-blue-700">Hacer mi diagnóstico</a>
            @endif
        </section>
    @else
        <h2 class="mt-8 text-2xl font-extrabold text-slate-900">Rutas completadas <span class="font-semibold text-slate-500">({{ $paths->count() }})</span></h2>
        <div class="mt-4 space-y-5">
            @foreach ($paths as $path)
                @php($certificate = $path->certificate)
                <article class="learning-panel grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:items-center" aria-labelledby="ruta-{{ $path->id }}">
                    <a href="{{ route('adaptive.certificates.show', $certificate) }}" class="block rounded-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500" aria-label="Ver el certificado de {{ $path->title }}">
                        @include('certificates.preview', $previews[$certificate->id])
                    </a>
                    <div>
                        <h3 id="ruta-{{ $path->id }}" class="text-xl font-extrabold text-slate-900">{{ $path->title }}</h3>
                        <p class="mt-1 text-sm text-slate-600">Completada el {{ $certificate->issued_at->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</p>
                        <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs font-bold text-slate-500">Fases</dt><dd class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-900">{{ $certificate->phases }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs font-bold text-slate-500">Temas</dt><dd class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-900">{{ $certificate->topics }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs font-bold text-slate-500">Horas</dt><dd class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-900">{{ $certificate->hours ?? '—' }}</dd></div>
                        </dl>
                        <details class="group mt-4">
                            <summary class="inline-flex min-h-10 cursor-pointer items-center text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Ver las fases que completaste</summary>
                            <ol class="mt-2 space-y-1.5 text-sm text-slate-700">
                                @foreach ($path->items as $item)
                                    <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>{{ $item->title }}</li>
                                @endforeach
                            </ol>
                        </details>
                        <div class="mt-5 flex flex-wrap gap-3" x-data="{ copied: false }">
                            <a href="{{ route('adaptive.certificates.pdf', $certificate) }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold text-white hover:bg-blue-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14"/></svg>Descargar PDF</a>
                            <a href="{{ route('adaptive.certificates.show', $certificate) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 hover:border-blue-400">Ver certificado</a>
                            <button type="button" @click="navigator.clipboard?.writeText(@js(route('certificates.verify', $certificate))).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
                                class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-blue-700 underline-offset-4 hover:underline" x-text="copied ? 'Enlace copiado' : 'Copiar enlace de verificación'">Copiar enlace de verificación</button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
