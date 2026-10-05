@extends('layouts.platform')

@section('content')
<div class="mx-auto w-full max-w-5xl">
    <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end">
        <div data-tour="notificaciones"><h1 class="text-3xl font-extrabold text-slate-900">Mantente al día</h1><p class="mt-2 text-slate-600">Mensajes, mentorías, avisos y recordatorios de Aprende Guate.</p></div>
        <div class="flex flex-wrap gap-3">@if($notifications->whereNull('read_at')->count())<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50">Marcar todas como leídas</button></form>@endif<a href="{{ route('profile.edit', ['section' => 'notifications']) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Configurar avisos</a></div>
    </div>

    @if($twoFactorPending)
        <section class="mt-6 rounded-2xl border border-orange-200 bg-orange-50 p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div class="flex gap-3"><span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-orange-500"></span><div><h2 class="font-extrabold text-orange-950">Protege tu cuenta con verificación en dos pasos</h2><p class="mt-1 text-sm leading-6 text-orange-900">Este recordatorio permanecerá aquí hasta que actives 2FA. Añade un código temporal además de tu contraseña.</p></div></div><a href="{{ route('profile.edit', ['section' => 'security']) }}" class="shrink-0 rounded-xl bg-orange-600 px-4 py-2.5 text-center text-sm font-extrabold text-white hover:bg-orange-700">Ir a seguridad</a></div></section>
    @endif

    <section class="mt-7 space-y-3">
        @forelse($notifications as $notification)
            <article class="rounded-2xl border p-5 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50/50' }}"><div class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-blue-600' }}"></span><div class="min-w-0 flex-1"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><h2 class="font-extrabold text-slate-900">{{ data_get($notification->data, 'title') }}</h2><p class="shrink-0 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p></div><p class="mt-2 text-sm leading-6 text-slate-600">{{ data_get($notification->data, 'body') }}</p><form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="mt-4">@csrf<button class="text-sm font-bold text-blue-700 hover:text-blue-900">{{ data_get($notification->data, 'action_label', 'Ver detalle') }} →</button></form></div></div></article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-12 text-center"><p class="font-bold text-slate-700">No tienes notificaciones todavía.</p><p class="mt-2 text-sm text-slate-500">Aquí aparecerán las respuestas de mentoría, mensajes y avisos importantes.</p></div>
        @endforelse
        {{ $notifications->links() }}
    </section>
</div>
@endsection
