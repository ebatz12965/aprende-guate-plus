@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-2xl">
    <section class="rounded-3xl border border-blue-100 bg-white p-8 text-center shadow-sm" x-data x-init="setTimeout(() => window.location.reload(), 3000)">
        <span class="ag-soft-pulse ag-brand-loading mx-auto grid h-14 w-14 place-items-center rounded-2xl p-2 text-2xl text-white"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="h-full w-full rounded-xl object-cover"><span class="sr-only">Cargando</span></span>
        <p class="mt-6 text-sm font-extrabold uppercase tracking-wide text-blue-600">Diagnóstico en preparación</p>
        <h1 class="mt-2 text-3xl font-black text-slate-900">Estamos preparando tus preguntas</h1>
        <p class="mx-auto mt-3 max-w-lg leading-7 text-slate-600">Adaptamos el diagnóstico a tu meta: «{{ \Illuminate\Support\Str::limit($attempt->primary_answer, 120) }}». Suele tardar unos segundos.</p>
        <p class="ag-soft-pulse mt-5 text-sm font-semibold text-blue-700">Esta página se actualiza automáticamente.</p>
    </section>
</div>
@endsection
