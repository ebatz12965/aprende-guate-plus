@extends('layouts.admin')

@section('content')
<div class="ai-pulse max-w-[1500px]">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Panel administrativo</h1>
            <p class="mt-2 max-w-3xl text-slate-600">Consumo de los agentes de IA: quién la usa, con qué modelos, para qué tareas y cuánto cuesta. Toca cualquier dato para ver su detalle.</p>
        </div>
        <p class="inline-flex items-center gap-2 text-sm font-bold text-emerald-700" role="status"><span class="ai-pulse-dot"></span><span data-pulse-updated>En vivo</span></p>
    </div>
    @include('admin.partials.dashboard-tabs')

    <div class="flex flex-wrap items-center gap-3" role="group" aria-label="Filtros del tablero">
        <div class="inline-flex gap-0.5 rounded-xl border border-slate-200 bg-white p-1" data-pulse-ranges>
            @foreach ([60 => 'Última hora', 1440 => 'Hoy', 10080 => '7 días', 43200 => '30 días'] as $minutes => $label)
                <button type="button" data-range="{{ $minutes }}" aria-pressed="{{ $minutes === 60 ? 'true' : 'false' }}"
                    class="min-h-10 rounded-lg px-3 text-sm font-bold text-slate-600 transition hover:text-slate-900 aria-pressed:bg-blue-50 aria-pressed:text-blue-700">{{ $label }}</button>
            @endforeach
        </div>
        <label for="pulse-provider" class="sr-only">Proveedor</label>
        <select id="pulse-provider" data-pulse-provider class="min-h-11 rounded-xl border-slate-200 text-sm font-semibold">
            <option value="">Todos los proveedores</option>
            @foreach ($providers as $provider)<option value="{{ $provider['id'] }}">{{ $provider['name'] }}</option>@endforeach
        </select>
        @unless ($initial['has_prices'])
            <a href="{{ route('admin.ai-orchestrator.index') }}#precios" class="text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Agrega los precios por modelo para ver el costo estimado</a>
        @endunless
    </div>

    <div class="mt-5 space-y-6" data-pulse-root>
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Cifras clave" data-pulse-kpis></section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-3 flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                <div><h2 class="font-extrabold text-slate-900">Tokens en el tiempo</h2><p class="mt-1 text-sm text-slate-600">Por proveedor · se actualiza cada 5 segundos</p></div>
                <div class="flex flex-wrap items-center gap-4"><div class="ai-pulse-legend" data-pulse-legend></div><button type="button" class="text-sm font-bold text-blue-700 hover:text-blue-900" data-pulse-table-toggle>Ver tabla</button></div>
            </div>
            <div class="ai-pulse-chart" data-pulse-area><div class="ai-pulse-tip" data-pulse-tip></div></div>
            <div class="overflow-x-auto" data-pulse-area-table hidden></div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="font-extrabold text-slate-900">Top 10 usuarios</h2><p class="mt-1 text-sm text-slate-600">Tokens consumidos en el periodo</p><div class="mt-4 grid gap-1" data-pulse-users></div></section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="font-extrabold text-slate-900">Consumo por modelo</h2><p class="mt-1 text-sm text-slate-600">Tokens y costo estimado</p><div class="mt-4 grid gap-1" data-pulse-models></div></section>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="font-extrabold text-slate-900">Por agente</h2><p class="mt-1 text-sm text-slate-600">Qué tarea consume más</p><div class="mt-4 grid gap-1" data-pulse-agents></div></section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="font-extrabold text-slate-900">Resultado de las solicitudes</h2><p class="mt-1 text-sm text-slate-600">Éxito y causas de fallo</p><div class="mt-4" data-pulse-status></div></section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 lg:col-span-2 2xl:col-span-1"><h2 class="font-extrabold text-slate-900">Cuándo se usa</h2><p class="mt-1 text-sm text-slate-600">Solicitudes por día y hora · últimos 30 días</p><div class="mt-4" data-pulse-heat></div></section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-extrabold text-slate-900">Solicitudes en vivo</h2><p class="mt-1 text-sm text-slate-600">Las más recientes primero; toca una para ver su detalle</p>
            <div class="mt-4 overflow-x-auto"><table class="ai-pulse-feed"><thead><tr><th>Hora</th><th>Usuario</th><th>Agente</th><th>Modelo</th><th class="text-right">Tokens</th><th class="text-right">Duración</th><th>Estado</th></tr></thead><tbody data-pulse-feed></tbody></table></div>
        </section>
    </div>
</div>

<div class="ai-pulse-drawer" data-pulse-drawer hidden>
    <div class="ai-pulse-drawer-bg" data-pulse-close></div>
    <aside class="ai-pulse-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="pulse-detail-title" tabindex="-1">
        <header class="flex items-start justify-between gap-3">
            <div><p class="text-sm font-bold text-blue-700" data-detail-kind></p><h2 id="pulse-detail-title" class="text-2xl font-extrabold text-slate-900" data-detail-title></h2></div>
            <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-slate-200 text-xl text-slate-700 hover:bg-slate-50" aria-label="Cerrar detalle" data-pulse-close>×</button>
        </header>
        <div class="grid grid-cols-3 gap-2" data-detail-stats></div>
        <div data-detail-chart-wrap><h3 class="mb-2 text-sm font-extrabold text-slate-900">Tokens en el periodo</h3><div data-detail-chart></div></div>
        <div><h3 class="mb-2 text-sm font-extrabold text-slate-900" data-detail-list-title></h3><ul class="ai-pulse-list" data-detail-list></ul></div>
        <div data-detail-recent-wrap><h3 class="mb-2 text-sm font-extrabold text-slate-900">Últimas solicitudes</h3><ul class="ai-pulse-list" data-detail-recent></ul></div>
    </aside>
</div>

<script type="application/json" id="ai-pulse-config">@json(['initial' => $initial, 'dataUrl' => route('admin.ai-pulse.data'), 'detailUrl' => route('admin.ai-pulse.detail')])</script>
@vite('resources/js/ai-pulse.js')
@endsection
