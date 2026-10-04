@extends('layouts.platform')
@section('content')
@php($milestones = [25, 50, 75, 100])
<div class="mx-auto max-w-7xl">
    <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl sm:px-9">
        <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-blue-400/20 blur-3xl"></div>
        <div class="relative">
            <p class="text-xs font-extrabold tracking-[.18em] text-blue-300">RETOS OPCIONALES DE TU RUTA</p>
            <div class="mt-3 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-3xl font-extrabold">Practica lo que ya has aprendido</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Los retos se desbloquean al 25 %, 50 %, 75 % y 100 % de tu ruta. Son opcionales: no bloquean tu avance y se generan únicamente con los temas que ya completaste.</p>
                    @if($path)<p class="mt-3 text-sm font-semibold text-blue-100">Ruta: {{ $path->title }} · {{ $progress }} % completado</p>@endif
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm"><b>{{ $submissions->count() }}</b> evidencias registradas</div>
            </div>
        </div>
    </section>

    @if(! $path)
        <section class="learning-panel mt-6 text-center"><h2 class="text-xl font-extrabold text-slate-900">Aún no tienes una ruta activa</h2><p class="mt-2 text-sm text-slate-600">Completa el diagnóstico para obtener una ruta personalizada y desbloquear retos relacionados con ella.</p><a class="mt-5 inline-flex rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white" href="{{ route('adaptive.diagnostic') }}">Crear mi ruta</a></section>
    @else
        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($milestones as $milestone)
                @php($challenge = $challengesByMilestone->get($milestone))
                <article class="challenge-milestone-card rounded-2xl border p-5 {{ $progress >= $milestone ? 'border-blue-200 bg-white shadow-sm' : 'border-slate-200 bg-slate-50' }}">
                    <div class="flex items-center justify-between"><span class="challenge-milestone-badge rounded-full px-2.5 py-1 text-xs font-extrabold {{ $progress >= $milestone ? 'bg-blue-50 text-blue-700' : 'bg-slate-200 text-slate-600' }}">{{ $milestone }} %</span><span class="text-xs font-bold {{ $progress >= $milestone ? 'text-emerald-600' : 'text-slate-400' }}">{{ $progress >= $milestone ? 'Desbloqueado' : 'Bloqueado' }}</span></div>
                    <h2 class="mt-4 font-extrabold text-slate-900">Reto {{ $loop->iteration }}</h2>
                    @if($progress < $milestone)
                        <p class="mt-2 text-sm leading-6 text-slate-500">Completa el {{ $milestone }} % de tu ruta para recibir un reto acorde a ese nivel.</p>
                    @elseif(! $challenge || in_array($challenge->generation_status, ['queued', 'processing']))
                        <p class="ag-soft-pulse mt-2 flex items-center gap-2 text-sm leading-6 text-slate-600"><span class="ag-spinner text-blue-600"></span>Estamos preparando tu reto con los temas que ya completaste.</p>
                    @elseif($challenge->generation_status === 'failed')
                        <p class="mt-2 text-sm leading-6 text-rose-600">No se pudo preparar este reto todavía. Podrás reintentarlo desde soporte administrativo.</p>
                    @else
                        <p class="mt-2 text-sm font-bold leading-6 text-slate-800">{{ $challenge->title }}</p>
                        <a href="{{ route('adaptive.challenges.show', $challenge) }}" class="mt-4 inline-flex text-sm font-bold text-blue-700 hover:text-blue-800">Abrir reto →</a>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="mt-6 grid gap-5 md:grid-cols-2">
            @forelse($challenges->where('generation_status', 'completed') as $challenge)
                @php($submission = $submissions->get($challenge->id))
                <article class="learning-panel flex flex-col">
                    <div class="flex items-start justify-between gap-3"><div><div class="flex flex-wrap gap-2"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Hito {{ $challenge->milestone }} %</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $challenge->area }}</span></div><h2 class="mt-4 text-xl font-extrabold text-slate-900">{{ $challenge->title }}</h2></div><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $submission?->status === 'evaluated' ? 'bg-emerald-50 text-emerald-700' : ($submission ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700') }}">{{ $submission?->status === 'evaluated' ? 'Evaluada' : ($submission ? 'Evidencia enviada' : 'Opcional') }}</span></div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">{{ $challenge->description }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-3 text-sm"><div class="rounded-xl bg-slate-50 p-3"><p class="font-bold text-slate-800">Objetivos</p><p class="mt-1 text-slate-600">{{ count($challenge->objectives ?? []) }} resultados a demostrar</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="font-bold text-slate-800">Tiempo estimado</p><p class="mt-1 text-slate-600">{{ $challenge->estimated_minutes ? $challenge->estimated_minutes.' min' : 'A tu ritmo' }}</p></div></div>
                    <a href="{{ route('adaptive.challenges.show', $challenge) }}" class="mt-5 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700">{{ $submission ? 'Ver entrega y resultado' : 'Abrir reto completo' }} →</a>
                </article>
            @empty
                <div class="learning-panel md:col-span-2"><h2 class="text-xl font-extrabold text-slate-900">Todavía no hay retos disponibles</h2><p class="mt-2 text-sm text-slate-600">Al completar el primer 25 % de esta ruta se generará tu primer reto práctico personalizado.</p></div>
            @endforelse
        </section>
    @endif
</div>
@endsection
