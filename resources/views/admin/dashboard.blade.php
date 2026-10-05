@extends('layouts.admin')

@section('content')
<div class="max-w-[1500px]">
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Panel administrativo</h1>
        <p class="mt-2 text-slate-600">Controla usuarios, consumo de inteligencia artificial y servicios de Aprende Guate.</p>
    </div>
    @include('admin.partials.dashboard-tabs')

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <section class="admin-stat-card"><p>Usuarios registrados</p><strong>{{ number_format($totalUsers) }}</strong><span>Personas con una cuenta en la plataforma</span><a href="#usuarios-recientes" class="mt-3 inline-flex text-sm font-bold text-blue-700 hover:text-blue-900">Ver últimos registros ↓</a></section>
        <section class="admin-stat-card"><p>Tokens consumidos</p><strong>{{ number_format($tokensUsed) }}</strong><span>{{ number_format($usageRequests) }} solicitudes IA registradas</span><a href="{{ route('admin.ai-usage.index') }}" class="mt-3 inline-flex text-sm font-bold text-blue-700 hover:text-blue-900">Ver consumo detallado →</a></section>
        <section class="admin-stat-card"><p>Tokens disponibles</p>@if($tokensAvailable !== null)<strong>{{ number_format($tokensAvailable) }}</strong><span>de {{ number_format($tokenBudget) }} configurados como límite</span>@else<strong class="text-2xl">Sin límite</strong><span>Configura un presupuesto para calcular disponibilidad</span>@endif<a href="{{ route('admin.ai-settings.edit') }}" class="mt-3 inline-flex text-sm font-bold text-blue-700 hover:text-blue-900">Configurar límites →</a></section>
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-2">
        <section id="usuarios-recientes" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6"><div><h2 class="font-extrabold text-slate-900">Últimos usuarios registrados</h2><p class="mt-1 text-sm text-slate-500">Muestra hasta cinco registros recientes.</p></div><a href="{{ route('admin.users.index') }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Ver todos →</a></div><div class="divide-y divide-slate-100">@forelse($latestUsers as $user)<div class="flex items-center gap-3 px-5 py-4 sm:px-6"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-100 font-extrabold text-blue-800">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span><div class="min-w-0 flex-1"><p class="truncate font-bold text-slate-900">{{ $user->name }}</p><p class="truncate text-sm text-slate-500">{{ $user->email }}</p></div><div class="text-right text-xs text-slate-500"><p>{{ $user->created_at->format('d/m/Y') }}</p><p class="mt-1 font-semibold {{ $user->email_verified_at ? 'text-emerald-700' : 'text-amber-700' }}">{{ $user->email_verified_at ? 'Verificado' : 'Pendiente' }}</p></div></div>@empty<div class="px-5 py-8 text-center text-sm text-slate-500">Aún no hay usuarios registrados.</div>@endforelse</div></section>

        <section class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div><h2 class="font-extrabold text-slate-900">Inteligencia artificial hoy</h2><p class="mt-1 text-sm text-slate-600">Consumo en vivo por usuario, modelo, agente y proveedor.</p></div>
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>En vivo</span>
            </div>
            <dl class="mt-5 grid grid-cols-3 gap-4">
                <div><dt class="text-sm font-semibold text-slate-600">Tokens</dt><dd class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900">{{ number_format($aiToday['kpis']['tokens']) }}</dd></div>
                <div><dt class="text-sm font-semibold text-slate-600">Solicitudes</dt><dd class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900">{{ number_format($aiToday['kpis']['requests']) }}</dd></div>
                <div><dt class="text-sm font-semibold text-slate-600">Correctas</dt><dd class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900">{{ $aiToday['kpis']['ok_rate'] === null ? '—' : $aiToday['kpis']['ok_rate'].' %' }}</dd></div>
            </dl>
            @if ($topModel = $aiToday['models'][0] ?? null)
                <p class="mt-4 text-sm text-slate-600">Modelo con más consumo: <span class="font-bold text-slate-900">{{ $topModel['id'] }}</span> ({{ number_format($topModel['tokens']) }} tokens).</p>
            @endif
            <a href="{{ route('admin.ai-pulse.index') }}" class="mt-auto inline-flex min-h-11 items-center self-start pt-4 text-sm font-bold text-blue-700 hover:text-blue-900">Abrir el tablero de IA →</a>
        </section>
    </div>

    <div class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><p class="text-sm font-bold text-slate-900">Configuración operativa</p><p class="mt-1 text-sm leading-6 text-slate-600">Gestiona los límites de IA, el orquestador de agentes y el correo saliente desde un mismo espacio.</p><div class="mt-4 flex flex-wrap gap-3"><a href="{{ route('admin.ai-settings.edit') }}" class="admin-secondary-button">Límites de IA</a><a href="{{ route('admin.ai-orchestrator.index') }}" class="admin-secondary-button">Orquestador IA</a><a href="{{ route('admin.mail-settings.edit') }}" class="admin-secondary-button">Correo saliente</a></div></div>
</div>
@endsection