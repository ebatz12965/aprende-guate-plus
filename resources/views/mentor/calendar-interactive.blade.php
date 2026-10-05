@extends('layouts.platform')

@section('content')
@php
    $calendarSessions = $sessions->map(fn ($session) => [
        'id' => $session->id,
        'title' => $session->topic,
        'student' => $session->student?->name ?? 'Estudiante',
        'start' => $session->scheduled_at->toIso8601String(),
        'duration' => (int) ($session->duration_minutes ?: 30),
        'url' => route('mentor.sessions.show', $session),
    ])->values();
@endphp

<div class="mx-auto w-full max-w-7xl" x-data="mentorCalendar(@js($calendarSessions), @js(request('view', 'month')), @js(request('date', now()->toDateString())))">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Calendario</h1>
            <p class="mt-2 text-slate-600">Organiza tus sesiones y consulta tu disponibilidad por día, semana o mes.</p>
        </div>
        <a href="{{ route('mentor.dashboard') }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">← Solicitudes</a>
    </div>

    <section class="learning-panel mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <button type="button" @click="previous()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50" aria-label="Periodo anterior">←</button>
                <button type="button" @click="goToday()" class="rounded-xl border border-blue-200 px-3 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50">Hoy</button>
                <button type="button" @click="next()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50" aria-label="Periodo siguiente">→</button>
                <h2 class="ml-2 text-lg font-extrabold capitalize text-slate-900" x-text="heading()"></h2>
            </div>
            <div class="flex rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Vista del calendario">
                <template x-for="option in viewOptions" :key="option.value">
                    <button type="button" @click="setView(option.value)" :aria-selected="view === option.value" :class="view === option.value ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-3 py-2 text-sm font-bold transition" x-text="option.label"></button>
                </template>
            </div>
        </div>

        <div x-cloak x-show="view === 'month'" class="overflow-x-auto">
            <div class="min-w-[720px]">
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
                    <template x-for="day in weekDayLabels" :key="day"><div class="px-3 py-3 text-center text-xs font-extrabold uppercase tracking-wide text-slate-500" x-text="day"></div></template>
                </div>
                <div class="grid grid-cols-7">
                    <template x-for="cell in monthCells()" :key="cell.key">
                        <div :class="cell.inMonth ? 'bg-white' : 'bg-slate-50/70 dark:bg-slate-800/40'" class="min-h-[128px] border-b border-r border-slate-200 p-2">
                            <div class="mb-2 flex justify-end"><span :class="cell.key === todayKey() ? 'bg-blue-600 text-white' : (cell.inMonth ? 'text-slate-700' : 'text-slate-500')" class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-extrabold" x-text="cell.day"></span></div>
                            <div class="space-y-1">
                                <template x-for="session in sessionsFor(cell.key)" :key="session.id">
                                    <a :href="session.url" class="block truncate rounded-lg border border-blue-100 bg-blue-50 px-2 py-1.5 text-xs font-bold text-blue-800 transition hover:border-blue-300 hover:bg-blue-100" :title="session.title" x-text="formatTime(session.start) + ' · ' + session.title"></a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-cloak x-show="view === 'week'" class="overflow-x-auto p-4">
            <div class="grid min-w-[840px] grid-cols-7 overflow-hidden rounded-xl border border-slate-200">
                <template x-for="day in weekCells()" :key="day.key">
                    <div class="min-h-[390px] border-r border-slate-200 last:border-r-0">
                        <div :class="day.key === todayKey() ? 'bg-blue-50 text-blue-700' : 'bg-slate-50 text-slate-700'" class="border-b border-slate-200 px-3 py-3 text-center"><p class="text-xs font-extrabold uppercase" x-text="day.label"></p><p class="mt-1 text-lg font-extrabold" x-text="day.day"></p></div>
                        <div class="space-y-2 p-2">
                            <template x-for="session in sessionsFor(day.key)" :key="session.id">
                                <a :href="session.url" class="block rounded-lg border border-blue-100 bg-blue-50 p-2 text-xs text-blue-900 transition hover:border-blue-300 hover:bg-blue-100"><p class="font-extrabold" x-text="formatTime(session.start)"></p><p class="mt-1 font-semibold" x-text="session.title"></p><p class="mt-1 text-blue-700" x-text="session.student"></p></a>
                            </template>
                            <p x-show="sessionsFor(day.key).length === 0" class="px-1 py-3 text-center text-xs text-slate-500">Sin sesiones</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-cloak x-show="view === 'day'" class="p-4">
            <div class="rounded-xl border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-extrabold text-slate-700" x-text="formatLongDate(date)"></div>
                <div class="divide-y divide-slate-100">
                    <template x-for="session in sessionsFor(date)" :key="session.id">
                        <a :href="session.url" class="flex flex-col gap-2 px-4 py-4 transition hover:bg-blue-50 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-extrabold text-blue-700" x-text="formatTime(session.start) + ' · ' + session.duration + ' min'"></p><p class="mt-1 font-extrabold text-slate-900" x-text="session.title"></p><p class="mt-1 text-sm text-slate-500" x-text="session.student"></p></div><span class="text-sm font-bold text-blue-700">Abrir sesión →</span></a>
                    </template>
                    <p x-show="sessionsFor(date).length === 0" class="px-4 py-12 text-center text-sm text-slate-500">No tienes sesiones programadas para este día.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    function mentorCalendar(sessions, initialView, initialDate) {
        return {
            sessions,
            view: ['day', 'week', 'month'].includes(initialView) ? initialView : 'month',
            date: initialDate || ((d) => [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-'))(new Date()),
            viewOptions: [{ value: 'day', label: 'Día' }, { value: 'week', label: 'Semana' }, { value: 'month', label: 'Mes' }],
            weekDayLabels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            init() { this.syncUrl(); },
            parse(value) { const [year, month, day] = value.split('-').map(Number); return new Date(year, month - 1, day); },
            key(date) { return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-'); },
            todayKey() { return this.key(new Date()); },
            sessionsFor(date) { return this.sessions.filter(session => session.start.slice(0, 10) === date).sort((a, b) => a.start.localeCompare(b.start)); },
            formatTime(value) { return new Date(value).toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' }); },
            formatLongDate(value) { return this.parse(value).toLocaleDateString('es-GT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); },
            monthCells() { const current = this.parse(this.date); const first = new Date(current.getFullYear(), current.getMonth(), 1); const offset = (first.getDay() + 6) % 7; const start = new Date(current.getFullYear(), current.getMonth(), 1 - offset); return Array.from({ length: 42 }, (_, index) => { const cell = new Date(start); cell.setDate(start.getDate() + index); return { key: this.key(cell), day: cell.getDate(), inMonth: cell.getMonth() === current.getMonth() }; }); },
            weekCells() { const current = this.parse(this.date); const offset = (current.getDay() + 6) % 7; const start = new Date(current); start.setDate(current.getDate() - offset); return Array.from({ length: 7 }, (_, index) => { const day = new Date(start); day.setDate(start.getDate() + index); return { key: this.key(day), day: day.getDate(), label: this.weekDayLabels[index] }; }); },
            heading() { const current = this.parse(this.date); if (this.view === 'day') return this.formatLongDate(this.date); if (this.view === 'week') { const days = this.weekCells(); return `${days[0].day}–${days[6].day} de ${current.toLocaleDateString('es-GT', { month: 'long', year: 'numeric' })}`; } return current.toLocaleDateString('es-GT', { month: 'long', year: 'numeric' }); },
            shift(amount) { const current = this.parse(this.date); if (this.view === 'day') current.setDate(current.getDate() + amount); else if (this.view === 'week') current.setDate(current.getDate() + amount * 7); else current.setMonth(current.getMonth() + amount); this.date = this.key(current); this.syncUrl(); },
            previous() { this.shift(-1); },
            next() { this.shift(1); },
            goToday() { this.date = this.todayKey(); this.syncUrl(); },
            setView(view) { this.view = view; this.syncUrl(); },
            syncUrl() { const url = new URL(window.location.href); url.searchParams.set('view', this.view); url.searchParams.set('date', this.date); window.history.replaceState({}, '', url); },
        };
    }
</script>
@endsection
