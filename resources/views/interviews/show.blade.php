@extends('layouts.platform')

@section('content')
@use('App\Models\InterviewStage')
@php
    $initials = fn (string $name) => collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('');
    $statusChip = fn (string $status) => match ($status) {
        'available' => 'bg-blue-50 text-blue-800 ring-blue-200',
        'in_progress' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'submitted', 'evaluated' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'preparing' => 'bg-blue-50 text-blue-800 ring-blue-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp
<div class="mx-auto max-w-5xl">
    <a href="{{ route('adaptive.interviews') }}" class="inline-flex min-h-10 items-center text-sm font-bold text-blue-700 hover:text-blue-900">← Empleabilidad</a>

    @if ($simulation->status === 'generating')
        <section class="mt-4 rounded-3xl border border-blue-100 bg-white p-8 text-center shadow-sm"
            x-data="{ async check() { try { const r = await fetch('{{ route('adaptive.interviews.status', $simulation) }}', { headers: { Accept: 'application/json' } }); if (r.ok && (await r.json()).status !== 'generating') window.location.reload(); } catch (e) {} setTimeout(() => this.check(), 3000); } }"
            x-init="setTimeout(() => check(), 3000)">
            <span class="ag-soft-pulse ag-brand-loading mx-auto grid h-14 w-14 place-items-center rounded-2xl p-2"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt=""></span>
            <h1 class="mt-6 text-2xl font-black text-slate-900">Estamos preparando tu proceso para «{{ $simulation->job_title }}»</h1>
            <p class="mx-auto mt-3 max-w-lg leading-7 text-slate-600">Armamos una empresa ficticia, las personas que te entrevistarán y las cuatro etapas. Suele tardar menos de un minuto; puedes seguir navegando.</p>
            <p class="ag-soft-pulse mt-5 text-sm font-semibold text-blue-700" aria-live="polite">Esta página se actualiza sola.</p>
        </section>
    @elseif ($simulation->status === 'failed')
        <section class="mt-4 rounded-3xl border border-rose-100 bg-white p-8 text-center shadow-sm">
            <h1 class="text-2xl font-black text-slate-900">No pudimos preparar el proceso</h1>
            <p class="mx-auto mt-3 max-w-lg leading-7 text-slate-600">{{ $simulation->generation_error }}</p>
            <form method="POST" action="{{ route('adaptive.interviews.retry', $simulation) }}" class="mt-6">@csrf<button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">Intentar de nuevo</button></form>
        </section>
    @else
        <section class="mt-4 learning-panel">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div class="min-w-0">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $simulation->job_title }}</h1>
                    <p class="mt-1 text-slate-600">{{ $simulation->areaLabel() }} · {{ $simulation->seniorityLabel() }}</p>
                </div>
                @php $done = $simulation->stages->whereIn('status', InterviewStage::FINISHED)->count(); @endphp
                <div class="shrink-0 text-left sm:text-right">
                    <p class="text-sm font-semibold text-slate-600">Avance</p>
                    <p class="text-2xl font-extrabold tabular-nums text-slate-900">{{ $done }} de {{ $simulation->stages->count() }}</p>
                </div>
            </div>
            @if ($simulation->company)
                <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                    <p class="text-sm font-bold text-slate-900">{{ $simulation->company['name'] }} <span class="ml-1 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">Empresa ficticia</span></p>
                    <p class="mt-1 text-sm text-slate-600">{{ $simulation->company['sector'] }}. {{ $simulation->company['description'] }}</p>
                </div>
            @endif
            @if ($simulation->summary)<p class="mt-4 leading-7 text-slate-700">{{ $simulation->summary }}</p>@endif
            @if ($simulation->generated_by === 'plantilla')
                <p class="mt-3 text-sm text-slate-500">La IA no estaba disponible, así que usamos un proceso típico del área.</p>
            @endif
        </section>

        <ol class="mt-6 space-y-4" aria-label="Etapas del proceso">
            @foreach ($simulation->stages as $stage)
                @php $locked = $stage->status === 'locked'; @endphp
                <li class="rounded-2xl border bg-white p-5 shadow-sm sm:p-6 {{ $locked ? 'border-slate-200 opacity-80' : 'border-blue-200' }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-extrabold {{ $locked ? 'bg-slate-100 text-slate-500' : 'bg-blue-700 text-white' }}">{{ $stage->position }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-extrabold text-slate-900">{{ $stage->title }}</h2>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $statusChip($stage->status) }}">{{ InterviewStage::STATUSES[$stage->status] ?? $stage->status }}</span>
                                <span class="text-sm text-slate-500">{{ $stage->duration_minutes }} min</span>
                            </div>
                            @if ($stage->interviewer)
                                <div class="mt-3 flex items-center gap-3">
                                    <span class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-xs font-extrabold text-slate-700" aria-hidden="true">{{ $initials($stage->interviewer['name']) }}</span>
                                    <p class="text-sm"><span class="font-bold text-slate-900">{{ $stage->interviewer['name'] }}</span> <span class="text-slate-600">· {{ $stage->interviewer['role'] }}</span></p>
                                </div>
                            @endif
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $stage->purpose }}</p>
                            @if ($stage->focus)
                                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Qué se evalúa">
                                    @foreach ($stage->focus as $topic)
                                        <li class="rounded-lg bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">{{ $topic }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="shrink-0">
                            @if ($locked)
                                <span class="inline-flex min-h-11 items-center text-sm text-slate-500">Se abre al terminar la etapa {{ $stage->position - 1 }}</span>
                            @else
                                @if ($stage->score !== null)<p class="mb-2 text-right text-sm font-bold tabular-nums text-slate-700">{{ $stage->score }} de 100</p>@endif
                                <a href="{{ route('adaptive.interviews.stage', [$simulation, $stage]) }}" class="inline-flex min-h-11 items-center rounded-xl px-5 text-sm font-bold {{ $stage->isFinished() ? 'border border-slate-300 bg-white text-slate-700 hover:border-blue-400' : 'bg-blue-700 text-white hover:bg-blue-800' }}">{{ match ($stage->status) {
                                    'in_progress', 'preparing' => 'Continuar',
                                    'submitted', 'evaluated' => 'Ver resultado',
                                    'skipped' => 'Ver la etapa',
                                    default => 'Empezar',
                                } }}</a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    <details class="mt-8 rounded-2xl border border-slate-200 bg-white p-5">
        <summary class="cursor-pointer text-sm font-bold text-slate-700">Eliminar esta simulación</summary>
        <p class="mt-2 text-sm text-slate-600">Se borran el proceso y todas tus respuestas. No se puede deshacer.</p>
        <form method="POST" action="{{ route('adaptive.interviews.destroy', $simulation) }}" class="mt-3">
            @csrf @method('DELETE')
            <button class="inline-flex min-h-10 items-center rounded-xl border border-rose-300 px-4 text-sm font-bold text-rose-800 hover:bg-rose-50">Eliminar simulación</button>
        </form>
    </details>
</div>
@endsection
