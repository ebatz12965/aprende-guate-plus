@extends('layouts.admin')

@section('content')
@use('App\Support\Audit')
@php
    $logChip = fn (?string $log) => match ($log) {
        'usuarios' => 'bg-blue-50 text-blue-800',
        'roles' => 'bg-violet-50 text-violet-800',
        'acceso' => 'bg-rose-50 text-rose-800',
        'configuracion' => 'bg-amber-50 text-amber-800',
        'mentores' => 'bg-emerald-50 text-emerald-800',
        default => 'bg-slate-100 text-slate-700',
    };
    $field = 'mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
@endphp
<div class="max-w-[1500px]">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Bitácora</h1>
        <p class="mt-2 text-slate-600">Quién hizo cada cambio administrativo y cuándo. No guarda contraseñas ni claves: solo qué cambió.</p>
    </div>

    <form method="GET" class="mt-7 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-end">
        @if ($account)<input type="hidden" name="cuenta" value="{{ $account->id }}">@endif
        <div>
            <label for="categoria" class="block text-sm font-semibold text-slate-700">Categoría</label>
            <select id="categoria" name="categoria" class="{{ $field }}">
                <option value="">Todas</option>
                @foreach (Audit::LOGS as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['categoria'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="quien" class="block text-sm font-semibold text-slate-700">Hecho por</label>
            <input id="quien" name="quien" type="search" value="{{ $filters['quien'] ?? '' }}" placeholder="Nombre o correo" class="{{ $field }}">
        </div>
        <div>
            <label for="desde" class="block text-sm font-semibold text-slate-700">Desde</label>
            <input id="desde" name="desde" type="date" value="{{ $filters['desde'] ?? '' }}" class="{{ $field }}">
        </div>
        <div>
            <label for="hasta" class="block text-sm font-semibold text-slate-700">Hasta</label>
            <input id="hasta" name="hasta" type="date" value="{{ $filters['hasta'] ?? '' }}" class="{{ $field }}">
        </div>
        <div class="flex gap-2">
            <button class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-800">Filtrar</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.audit.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Limpiar</a>
            @endif
        </div>
    </form>

    @if ($account)
        <p class="mt-4 text-sm text-slate-600">Mostrando solo la cuenta de <a href="{{ route('admin.users.edit', $account) }}" class="font-bold text-blue-700 hover:text-blue-900">{{ $account->name }}</a>.</p>
    @endif

    <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <ol class="divide-y divide-slate-100">
            @forelse ($activities as $activity)
                <li class="grid gap-2 px-5 py-4 sm:grid-cols-[10rem_minmax(0,1fr)] sm:px-6">
                    <div class="text-sm text-slate-500">
                        <time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('d/m/Y H:i') }}</time>
                        <span class="mt-1 block w-fit rounded-full px-2 py-0.5 text-xs font-bold {{ $logChip($activity->log_name) }}">{{ Audit::LOGS[$activity->log_name] ?? $activity->log_name }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-900">
                            <span class="font-bold">{{ $activity->causer?->name ?? 'Sistema' }}</span>
                            <span>{{ Str::lcfirst($activity->description) }}</span>
                            @if ($activity->subject instanceof \App\Models\User && ! $activity->subject->is($activity->causer))
                                · <a href="{{ route('admin.users.edit', $activity->subject) }}" class="font-semibold text-blue-700 hover:text-blue-900">{{ $activity->subject->name }}</a>
                            @endif
                        </p>
                        @include('admin.partials.activity-properties', ['activity' => $activity])
                    </div>
                </li>
            @empty
                <li class="px-6 py-12 text-center text-sm text-slate-500">{{ array_filter($filters) ? 'No hay actividad con estos filtros.' : 'Todavía no hay actividad registrada.' }}</li>
            @endforelse
        </ol>
        <div class="border-t border-slate-100 px-5 py-4">{{ $activities->links() }}</div>
    </section>
</div>
@endsection
