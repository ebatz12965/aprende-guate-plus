@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-2xl">
    <section class="rounded-3xl border border-blue-100 bg-white p-8 text-center shadow-sm" @if($attempt->status === 'generating') x-data x-init="setTimeout(() => window.location.reload(), 4000)" @endif>
        @if($attempt->status === 'generating')
            <span class="ag-soft-pulse ag-brand-loading mx-auto grid h-14 w-14 place-items-center rounded-2xl p-2 text-2xl text-white"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="h-full w-full rounded-xl object-cover"><span class="sr-only">Cargando</span></span>
            <p class="mt-6 text-sm font-extrabold uppercase tracking-wide text-blue-600">Ruta en preparación</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Estamos organizando tu ruta personalizada</h1>
            <p class="mx-auto mt-3 max-w-lg leading-7 text-slate-600">Puedes cerrar esta pantalla o seguir navegando. Te mostraremos la ruta apenas finalice la generación.</p>
            <p class="ag-soft-pulse mt-5 text-sm font-semibold text-blue-700">Esta página se actualiza automáticamente.</p>
        @elseif($attempt->status === 'failed')
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-rose-100 text-2xl text-rose-700">!</span>
            <p class="mt-6 text-sm font-extrabold uppercase tracking-wide text-rose-600">No se completó la generación</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Tu cuestionario sigue guardado</h1>
            <p class="mx-auto mt-3 max-w-lg leading-7 text-slate-600">{{ $attempt->generation_error }}</p>
            <form method="POST" action="{{ route('adaptive.diagnostic.retry', $attempt) }}" class="mt-6">@csrf<button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white">Reintentar generar ruta</button></form>
        @endif
    </section>
</div>
@endsection
