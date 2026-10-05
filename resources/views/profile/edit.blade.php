@extends('layouts.platform')

@section('content')
<div class="mx-auto w-full max-w-5xl" x-data="{ section: new URLSearchParams(window.location.search).get('section') || 'account' }">
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Ajustes de tu cuenta</h1>
        <p class="mt-2 text-slate-600">Organiza tu información, privacidad, seguridad y preferencias de comunicación.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">
        <nav class="h-fit rounded-2xl border border-slate-200 bg-white p-3 shadow-sm" aria-label="Secciones de configuración">
            <button @click="section = 'account'" :class="section === 'account' ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50'" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-extrabold transition">Cuenta</button>
            <button data-tour="seguridad" @click="section = 'security'" :class="section === 'security' ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50'" class="mt-1 flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-extrabold transition">Seguridad y privacidad @if(! $user->hasTwoFactorEnabled())<span class="ml-auto h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>@endif</button>
            <button @click="section = 'notifications'" :class="section === 'notifications' ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50'" class="mt-1 flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-extrabold transition">Notificaciones</button>
        </nav>

        <div class="min-w-0">
            <section x-show="section === 'account'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <div class="max-w-xl">@include('profile.partials.update-profile-information-form')</div>
            </section>

            <section x-show="section === 'security'" x-cloak class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"><div class="max-w-xl">@include('profile.partials.update-password-form')</div></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"><div class="max-w-xl">@include('profile.partials.two-factor-authentication-form')</div></div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-5 shadow-sm sm:p-8"><div class="max-w-xl">@include('profile.partials.delete-user-form')</div></div>
            </section>

            <section x-show="section === 'notifications'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <div class="max-w-xl">@include('profile.partials.notification-preferences-form')</div>
            </section>
        </div>
    </div>
</div>
@endsection
