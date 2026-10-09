@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-5xl">
    <a href="{{ route('adaptive.certificates') }}" class="inline-flex min-h-10 items-center text-sm font-bold text-blue-700 hover:text-blue-900">← Mis certificados</a>
    <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $certificate->title }}</h1>
            <p class="mt-1 text-slate-600">Certificado n.º <span class="font-semibold tabular-nums text-slate-800">{{ $certificate->code }}</span> · emitido el {{ $issuedOn }}</p>
        </div>
        <div class="flex flex-wrap gap-3" x-data="{ copied: false }">
            <button type="button" @click="navigator.clipboard?.writeText(@js($verifyUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
                class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 hover:border-blue-400" x-text="copied ? 'Enlace copiado' : 'Copiar enlace de verificación'">Copiar enlace de verificación</button>
            <a href="{{ route('adaptive.certificates.pdf', $certificate) }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold text-white hover:bg-blue-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14"/></svg>Descargar PDF</a>
        </div>
    </div>

    <div class="mt-6">@include('certificates.preview')</div>

    <p class="mt-4 text-sm leading-6 text-slate-600">Quien escanee el código QR o abra <a href="{{ $verifyUrl }}" class="font-semibold text-blue-700 underline-offset-4 hover:underline">{{ $verifyLabel }}</a> verá que el certificado es auténtico, con tu nombre, la ruta y la fecha. No se muestra ningún otro dato de tu cuenta.</p>
</div>
@endsection
