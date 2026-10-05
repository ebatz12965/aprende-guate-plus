@extends('layouts.platform')

@section('content')
<div class="mx-auto w-full max-w-6xl">
    <section class="rounded-3xl bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 p-7 text-white sm:p-9">
        <h1 class="text-3xl font-extrabold sm:text-4xl">Acompaña a estudiantes con contexto</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100 sm:text-base">Revisa las solicitudes, responde según tu especialidad y mantén conversaciones privadas una vez aceptadas.</p>
    </section>

    <div class="mt-7 grid gap-4 sm:grid-cols-3">
        <article class="learning-stat"><p>Solicitudes recibidas</p><strong>{{ $allSessions->count() }}</strong><span>historial total</span></article>
        <article class="learning-stat"><p>Pendientes</p><strong class="!text-amber-600">{{ $allSessions->where('status', 'requested')->count() }}</strong><span>requieren tu respuesta</span></article>
        <article class="learning-stat"><p>Mentorías activas</p><strong class="!text-emerald-600">{{ $allSessions->whereIn('status', ['accepted', 'scheduled'])->count() }}</strong><span>con conversación disponible</span></article>
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,.85fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between"><div><h2 class="text-xl font-extrabold text-slate-900">Organiza tus mentorías</h2><p class="mt-1 text-sm text-slate-500">Pasa sobre una solicitud para consultar el detalle.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $sessions->count() }} visibles</span></div>
            <form method="GET" action="{{ route('mentor.dashboard') }}" class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm font-extrabold text-slate-700">Filtrar por estado</p><button class="text-xs font-bold text-blue-700 hover:text-blue-900">Aplicar filtros</button></div><div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">@foreach(['requested'=>'Pendientes','accepted'=>'Aceptadas','scheduled'=>'Programadas','cancelled'=>'Canceladas','rejected'=>'Rechazadas'] as $status => $label)<label class="inline-flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="status[]" value="{{ $status }}" @checked(in_array($status, $selectedStatuses, true)) class="rounded border-slate-300 text-blue-600">{{ $label }} <span class="text-xs text-slate-500">({{ $allSessions->where('status', $status)->count() }})</span></label>@endforeach</div></form>
            <div class="mt-4 space-y-2">
                @forelse($sessions as $session)
                    @php($statusClass = match($session->status) { 'accepted', 'scheduled' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-rose-50 text-rose-700', default => 'bg-amber-50 text-amber-700' })
                    <article class="group relative flex items-center justify-between gap-4 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/40"><div class="min-w-0"><p class="truncate font-extrabold text-slate-900">{{ $session->student->name }}</p><p class="mt-1 truncate text-sm text-slate-600">{{ Str::limit($session->topic, 90) }}</p></div><div class="flex shrink-0 items-center gap-3"><span class="hidden rounded-full px-2.5 py-1 text-xs font-bold sm:inline-flex {{ $statusClass }}">{{ match($session->status) { 'requested' => 'Pendiente', 'accepted' => 'Aceptada', 'scheduled' => 'Programada', 'rejected' => 'Rechazada', 'cancelled' => 'Cancelada', default => ucfirst($session->status) } }}</span><a href="{{ route('mentor.sessions.show', $session) }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Ver →</a></div><div class="pointer-events-none invisible absolute left-4 right-4 top-full z-20 mt-2 rounded-xl border border-slate-200 bg-white p-4 text-sm opacity-0 shadow-xl transition group-hover:visible group-hover:opacity-100"><div class="flex items-center justify-between gap-3"><p class="font-extrabold text-slate-900">{{ $session->student->name }}</p><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ match($session->status) { 'requested' => 'Pendiente', 'accepted' => 'Aceptada', 'scheduled' => 'Programada', 'rejected' => 'Rechazada', 'cancelled' => 'Cancelada', default => ucfirst($session->status) } }}</span></div><p class="mt-2 font-semibold text-slate-800">{{ $session->topic }}</p><p class="mt-2 text-xs leading-5 text-slate-500">Objetivo: {{ $session->student->studentProfile?->professional_goal ?: 'No registrado' }} · Solicitud {{ $session->created_at->diffForHumans() }}</p>@if($session->scheduled_at)<p class="mt-1 text-xs font-semibold text-blue-700">Sesión: {{ $session->scheduled_at->format('d/m/Y H:i') }} · {{ $session->duration_minutes }} min</p>@endif</div></article>
                @empty
                    <div class="rounded-xl bg-slate-50 px-5 py-10 text-center"><p class="font-bold text-slate-700">No hay solicitudes con estos filtros.</p><p class="mt-2 text-sm text-slate-500">Las rechazadas están ocultas por defecto, pero puedes activarlas desde los filtros.</p></div>
                @endforelse
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
            <h2 class="text-xl font-extrabold text-slate-900">Organiza tu acompañamiento</h2><p class="mt-2 text-sm leading-6 text-slate-500">Administra calendario y sesiones desde sus apartados.</p><nav class="mt-6 space-y-2"><a href="{{ route('mentor.calendar') }}" class="block rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:border-blue-200 hover:bg-blue-50">Calendario <span class="float-right text-blue-700">→</span></a><a href="{{ route('mentor.sessions') }}" class="block rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:border-blue-200 hover:bg-blue-50">Sesiones y chats <span class="float-right text-blue-700">→</span></a></nav>
        </aside>
    </div>
</div>
@endsection
