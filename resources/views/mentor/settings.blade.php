@extends('layouts.platform')

@section('content')
<div class="mx-auto w-full max-w-4xl">
    <a href="{{ route('mentor.dashboard') }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">← Volver al panel</a>
    <div class="mt-5"><h1 class="text-3xl font-extrabold text-slate-900">Perfil, disponibilidad y mensajes</h1><p class="mt-2 text-slate-600">Esta información ayuda a organizar tus sesiones y responder a tus estudiantes.</p></div>
    <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form method="POST" action="{{ route('mentor.settings.update') }}" class="space-y-6">@csrf @method('PATCH')
            <div><label class="text-sm font-bold text-slate-700">Titular profesional</label><input name="headline" value="{{ old('headline', $profile->headline) }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="Ej. Mentor de empleabilidad digital"></div>
            <div><label class="text-sm font-bold text-slate-700">Especialidades</label><input name="specialties" value="{{ old('specialties', $profile->specialties) }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="Ej. Excel, datos y empleabilidad"></div>
            <div><label class="text-sm font-bold text-slate-700">Descripción</label><textarea name="about" rows="4" class="mt-2 w-full rounded-xl border-slate-200 text-sm">{{ old('about', $profile->about) }}</textarea></div>
            <div><label class="text-sm font-bold text-slate-700">Mensaje automático al aceptar</label><textarea name="acceptance_message" rows="5" required class="mt-2 w-full rounded-xl border-slate-200 text-sm leading-6">{{ old('acceptance_message', $profile->acceptance_message) }}</textarea><p class="mt-2 text-xs text-slate-500">Variables: {estudiante}, {tema}, {mentor}.</p></div>
            <div><label class="text-sm font-bold text-slate-700">Mensaje automático al rechazar</label><textarea name="rejection_message" rows="5" required class="mt-2 w-full rounded-xl border-slate-200 text-sm leading-6">{{ old('rejection_message', $profile->rejection_message) }}</textarea><p class="mt-2 text-xs text-slate-500">También puedes usar {motivo}.</p></div>
            <div><label class="text-sm font-bold text-slate-700">Duración de las sesiones (minutos)</label><input type="number" name="session_duration_minutes" min="5" max="240" required value="{{ old('session_duration_minutes', $profile->session_duration_minutes ?: 30) }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm"></div>
            <fieldset><legend class="text-sm font-bold text-slate-700">Disponibilidad habitual</legend><div class="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">@foreach(['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'Sábado','domingo'=>'Domingo'] as $day => $label)<label class="rounded-xl border border-slate-200 p-3"><span class="font-bold text-slate-700">{{ $label }}</span><span class="mt-2 flex flex-wrap gap-2">@foreach(['08:00-12:00','12:00-16:00','16:00-20:00'] as $slot)<label class="inline-flex items-center gap-1"><input type="checkbox" name="availability[{{ $day }}][]" value="{{ $slot }}" @checked(in_array($slot, data_get($profile->availability, $day, []), true))> {{ $slot }}</label>@endforeach</span></label>@endforeach</div></fieldset>
            <button class="w-full rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white hover:bg-blue-800">Guardar configuración</button>
        </form>
    </section>
</div>
@endsection
