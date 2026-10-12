@extends('layouts.platform')
@section('content')
<div class="mx-auto max-w-5xl">
@if(!$path)
    <div class="platform-feature"><h1 class="!mt-0 text-3xl">Tu ruta aún no está lista</h1><p class="mt-3 text-slate-600">Completa el diagnóstico para crear una ruta con módulos y temas según tu meta.</p><a href="{{ route('adaptive.diagnostic') }}" class="mt-5 inline-block rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Hacer diagnóstico</a></div>
@else
    @php($total = $phases->count())
    <div x-data="pathView({ current: {{ $currentPhase['index'] ?? 'null' }} })">
        {{-- Portada de la ruta: dónde estoy, cuánto llevo y cuál es el siguiente paso --}}
        <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl sm:px-9" aria-labelledby="ruta-titulo">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="min-w-0 max-w-2xl">
                    <h1 id="ruta-titulo" class="text-balance text-3xl font-extrabold tracking-tight">{{ $path->title }}</h1>
                    <p class="mt-2 leading-7 text-slate-300">{{ $path->goal }}</p>
                    <p class="mt-1 text-sm font-semibold text-sky-300">Ruta personalizada · nivel {{ \Illuminate\Support\Str::lower($path->estimated_level ?? 'Inicial') }} · {{ $path->estimated_hours ? $path->estimated_hours.' horas estimadas' : 'a tu ritmo' }}</p>
                </div>
                <p class="shrink-0 text-right"><span class="block text-4xl font-extrabold tabular-nums">{{ $progress }} %</span><span class="text-sm text-slate-300">{{ $completedPhases }} de {{ $total }} fases</span></p>
            </div>

            {{-- Barra por fases, con las marcas de los retos opcionales --}}
            <div class="relative mt-6" aria-hidden="true">
                <div class="flex gap-1">
                    @foreach ($phases as $phase)
                        <span class="h-2.5 flex-1 rounded-full {{ $phase['completed'] ? 'bg-blue-500' : ($phase['current'] ? 'bg-sky-300/70' : 'bg-white/15') }}" title="{{ $phase['item']->title }}"></span>
                    @endforeach
                </div>
                <div class="relative mt-1.5 h-4 text-[11px] font-bold text-slate-400">
                    @foreach ([25, 50, 75, 100] as $percent)
                        <span class="absolute -translate-x-full whitespace-nowrap {{ $progress >= $percent ? 'text-sky-300' : '' }}" style="left: {{ $percent }}%">Reto {{ $percent }} %</span>
                    @endforeach
                </div>
            </div>

            @if ($currentPhase)
                <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <a href="{{ route('adaptive.path.item.show', $currentPhase['item']) }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-500 px-5 text-sm font-bold text-slate-950 transition hover:bg-sky-300">
                        Continuar: fase {{ $currentPhase['item']->position }}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                    </a>
                    <p class="min-w-0 text-sm text-slate-300"><span class="font-bold text-white">{{ $currentPhase['item']->title }}</span> · {{ $currentPhase['topicsDone'] }} de {{ $currentPhase['topics'] }} temas</p>
                </div>
            @endif
        </section>

        {{-- Cambio de vista; la elección se recuerda en este navegador --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <details class="text-sm text-slate-600">
                <summary class="inline-flex min-h-10 cursor-pointer items-center font-bold text-blue-700 underline-offset-4 hover:underline">¿Cómo avanzo en mi ruta?</summary>
                <p class="mt-2 max-w-2xl rounded-xl bg-blue-50 px-4 py-3 leading-6 text-blue-900">Cada fase se desbloquea al aprobar el mini-quiz de la anterior. Dentro de cada tema generas su lección y su práctica cuando la abres. Al llegar al 25, 50, 75 y 100 % se desbloquea un reto opcional.</p>
            </details>
            <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 text-sm font-bold" role="group" aria-label="Vista de la ruta">
                <template x-for="option in options" :key="option.key">
                    <button type="button" @click="setView(option.key)" :aria-pressed="(view === option.key).toString()"
                        class="min-h-9 rounded-lg px-3 transition-colors" :class="view === option.key ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'" x-text="option.label"></button>
                </template>
            </div>
        </div>

        <div x-show="view === 'trail'">@include('adaptive.partials.path.trail')</div>
        <div x-show="view === 'compact'" x-cloak>@include('adaptive.partials.path.compact')</div>
        <div x-show="view === 'classic'" x-cloak>@include('adaptive.partials.path.classic')</div>
    </div>

    <script nonce="{{ Vite::cspNonce() }}">
        function pathView(config) {
            const KEY = 'ag-path-view';
            const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            return {
                options: [{ key: 'trail', label: 'Recorrido' }, { key: 'compact', label: 'Compacta' }, { key: 'classic', label: 'Clásica' }],
                view: 'trail',
                // La fase actual se ve abierta en el recorrido; en la compacta todo empieza resumido.
                defaults: { trail: config.current, compact: null },
                hovered: { trail: null, compact: null },
                pinned: { trail: null, compact: null },
                timers: {},
                init() {
                    try { const saved = localStorage.getItem(KEY); if (this.options.some((o) => o.key === saved)) this.view = saved; } catch (e) {}
                },
                setView(key) {
                    this.view = key;
                    try { localStorage.setItem(KEY, key); } catch (e) {}
                },
                openIndex(view) {
                    if (this.hovered[view] !== null) return this.hovered[view];
                    if (this.pinned[view] !== null) return this.pinned[view];
                    return this.defaults[view];
                },
                isOpen(view, index) { return this.openIndex(view) === index; },
                // Con una pequeña espera, para que las filas no salten al pasar el cursor por encima.
                hover(view, index) {
                    if (!canHover) return;
                    clearTimeout(this.timers[view]);
                    this.timers[view] = setTimeout(() => { this.hovered[view] = index; }, 140);
                },
                unhover(view) {
                    if (!canHover) return;
                    clearTimeout(this.timers[view]);
                    this.timers[view] = setTimeout(() => { this.hovered[view] = null; }, 260);
                },
                // Un clic (o un toque) fija la fase abierta; otro clic la cierra.
                toggle(view, index) {
                    clearTimeout(this.timers[view]);
                    if (this.hovered[view] === index) { this.pinned[view] = index; }
                    else { this.pinned[view] = this.isOpen(view, index) ? -1 : index; }
                    this.hovered[view] = null;
                },
                focusOpen(view, index) {
                    if (document.activeElement?.matches(':focus-visible')) { this.hovered[view] = null; this.pinned[view] = index; }
                },
            };
        }
    </script>
@endif
</div>
@endsection
