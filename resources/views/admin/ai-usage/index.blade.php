@extends('layouts.admin')

@section('content')
@php
    $format = fn ($value) => number_format((int) $value, 0, '.', ',');
    $maximum = max(1, (int) $dailyUsage->max('tokens'));
    $operationNames = ['tutor_reply' => 'Tutor IA', 'diagnostic_questions' => 'Preguntas de diagnóstico', 'learning_architecture' => 'Ruta de aprendizaje', 'topic_lesson' => 'Lección', 'topic_practice' => 'Práctica'];
@endphp
<div class="mx-auto max-w-7xl">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-bold text-blue-700">OBSERVABILIDAD DE IA</p>
            <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Consumo de tokens</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Uso registrado por la plataforma. Los tokens se obtienen de la respuesta del proveedor; no almacenamos prompts ni respuestas en este panel.</p>
        </div>
        <a href="{{ route('admin.ai-settings.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-700 transition hover:bg-blue-50">Configurar cuota de tokens</a>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <section class="platform-feature"><p class="text-sm font-semibold text-slate-500">Cuota configurada</p><p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $budget > 0 ? $format($budget) : 'Sin definir' }}</p><p class="mt-2 text-xs text-slate-500">Ingresa la cuota adquirida en Límites de IA.</p></section>
        <section class="platform-feature"><p class="text-sm font-semibold text-slate-500">Consumidos registrados</p><p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $format($totalConsumed) }}</p><p class="mt-2 text-xs text-slate-500">Histórico desde que se activó esta medición.</p></section>
        <section class="platform-feature"><p class="text-sm font-semibold text-slate-500">Disponibles estimados</p><p class="mt-2 text-3xl font-extrabold {{ $remaining === null ? 'text-slate-400' : ($remaining === 0 ? 'text-rose-600' : 'text-emerald-600') }}">{{ $remaining === null ? '—' : $format($remaining) }}</p><p class="mt-2 text-xs text-slate-500">{{ $usageRate === null ? 'Configura una cuota para calcularlo.' : $usageRate.'% de la cuota consumida.' }}</p></section>
        <section class="platform-feature"><p class="text-sm font-semibold text-slate-500">Últimos 30 días</p><p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $format($periodConsumed) }}</p><p class="mt-2 text-xs text-slate-500">{{ $format($successfulRequests) }} solicitudes exitosas de {{ $format($requests) }} registradas.</p></section>
    </div>

    <section class="platform-feature mt-6">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end"><div><h2 class="text-lg font-extrabold text-slate-900">Consumo diario</h2><p class="mt-1 text-sm text-slate-500">Pico: {{ $peak['tokens'] > 0 ? $format($peak['tokens']).' tokens el '.$peak['label'] : 'aún no hay consumo registrado' }}.</p></div><span class="text-sm font-bold text-blue-700">{{ $format($periodConsumed) }} tokens / 30 días</span></div>
        <div class="mt-6 flex h-52 items-end gap-1.5 border-b border-slate-200 pb-1" aria-label="Gráfica de consumo diario">@foreach ($dailyUsage as $day)<div class="group relative flex h-full flex-1 items-end"><div title="{{ $day['label'] }}: {{ $format($day['tokens']) }} tokens" class="w-full min-h-1 rounded-t bg-blue-500 transition group-hover:bg-blue-700" style="height: {{ max(2, round(($day['tokens'] / $maximum) * 100)) }}%"></div></div>@endforeach</div>
        <div class="mt-2 flex justify-between text-xs text-slate-400"><span>{{ $dailyUsage->first()['label'] }}</span><span>{{ $dailyUsage->get(14)['label'] }}</span><span>{{ $dailyUsage->last()['label'] }}</span></div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="platform-feature"><h2 class="text-lg font-extrabold text-slate-900">Usuarios con mayor consumo</h2><div class="mt-5 space-y-4">@forelse ($topConsumers as $index => $consumer)<div class="flex items-center justify-between gap-4"><div class="min-w-0"><p class="truncate font-bold text-slate-800">{{ $index + 1 }}. {{ $consumer->user?->name ?? 'Usuario eliminado' }}</p><p class="truncate text-xs text-slate-500">{{ $consumer->user?->email ?? 'Sin correo disponible' }} · {{ $format($consumer->requests) }} solicitudes</p></div><span class="shrink-0 rounded-lg bg-blue-50 px-2.5 py-1.5 text-sm font-extrabold text-blue-700">{{ $format($consumer->tokens) }}</span></div>@empty<p class="text-sm text-slate-500">Aún no hay consumos de usuarios registrados.</p>@endforelse</div></section>
        <section class="platform-feature"><h2 class="text-lg font-extrabold text-slate-900">Consumo por operación</h2><div class="mt-5 space-y-4">@forelse ($operations as $operation)<div class="flex items-center justify-between gap-4"><div><p class="font-bold text-slate-800">{{ $operationNames[$operation->operation] ?? $operation->operation }}</p><p class="text-xs text-slate-500">{{ $format($operation->requests) }} solicitudes</p></div><span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-sm font-extrabold text-slate-700">{{ $format($operation->tokens) }}</span></div>@empty<p class="text-sm text-slate-500">Aún no hay operaciones registradas.</p>@endforelse</div></section>
    </div>

    <section class="platform-feature mt-6 overflow-x-auto"><h2 class="text-lg font-extrabold text-slate-900">Actividad reciente</h2><table class="mt-5 min-w-full text-left text-sm"><thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500"><tr><th class="pb-3 pr-4">Fecha</th><th class="pb-3 pr-4">Usuario</th><th class="pb-3 pr-4">Operación</th><th class="pb-3 pr-4 text-right">Tokens</th><th class="pb-3 text-right">Estado</th></tr></thead><tbody>@forelse ($recentUsages as $usage)<tr class="border-b border-slate-100 last:border-0"><td class="py-3 pr-4 text-slate-500">{{ $usage->created_at->format('d/m/Y H:i') }}</td><td class="py-3 pr-4 font-medium text-slate-800">{{ $usage->user?->name ?? 'Sistema' }}</td><td class="py-3 pr-4 text-slate-600">{{ $operationNames[$usage->operation] ?? $usage->operation }}</td><td class="py-3 pr-4 text-right font-bold text-slate-800">{{ $format($usage->total_tokens) }}</td><td class="py-3 text-right"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $usage->successful ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $usage->successful ? 'Correcta' : 'Fallida' }}</span></td></tr>@empty<tr><td colspan="5" class="py-7 text-center text-slate-500">Las solicitudes futuras de IA aparecerán aquí.</td></tr>@endforelse</tbody></table></section>
</div>
@endsection
