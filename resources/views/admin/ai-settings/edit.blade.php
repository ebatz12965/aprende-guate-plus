@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <p class="text-sm font-bold text-indigo-600">CONFIGURACIÓN ADMINISTRATIVA</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Límites de IA y diagnóstico</h1>
        <p class="mt-2 text-slate-500">Estos valores se aplican a todos los usuarios. Se guardan en la plataforma, por lo que no necesitas editar el archivo .env para ajustarlos.</p>
    </div>

    <form action="{{ route('admin.ai-settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="platform-feature">
            <div class="flex items-start gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-100 font-bold text-indigo-700">IA</div>
                <div><h2 class="font-bold text-slate-900">Consultas al Tutor IA</h2><p class="mt-1 text-sm text-slate-500">El límite por minuto reduce abuso; el límite diario controla el consumo general por persona.</p></div>
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-bold text-slate-700">Consultas por minuto<input required min="1" max="60" type="number" name="tutor_minute_limit" value="{{ old('tutor_minute_limit', $settings['tutor_minute_limit']) }}" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"><span class="mt-1 block text-xs font-normal text-slate-500">Entre 1 y 60. Valor actual aplicado a la protección de peticiones.</span></label>
                <label class="block text-sm font-bold text-slate-700">Consultas por día<input required min="1" max="5000" type="number" name="tutor_daily_limit" value="{{ old('tutor_daily_limit', $settings['tutor_daily_limit']) }}" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"><span class="mt-1 block text-xs font-normal text-slate-500">Entre 1 y 5,000 por usuario. Valor recomendado: 60.</span></label>
            </div>
        </section>

        <section class="platform-feature">
            <div class="flex items-start gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100 font-bold text-sky-700">Aa</div>
                <div><h2 class="font-bold text-slate-900">Pregunta inicial del diagnóstico</h2><p class="mt-1 text-sm text-slate-500">Se aplica tanto al diagnóstico inicial como cuando el estudiante actualiza su diagnóstico.</p></div>
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-bold text-slate-700">Mínimo de caracteres<input required min="1" max="1000" type="number" name="diagnostic_min_characters" value="{{ old('diagnostic_min_characters', $settings['diagnostic_min_characters']) }}" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"><span class="mt-1 block text-xs font-normal text-slate-500">No puede ser mayor que el máximo.</span></label>
                <label class="block text-sm font-bold text-slate-700">Máximo de caracteres<input required min="10" max="5000" type="number" name="diagnostic_max_characters" value="{{ old('diagnostic_max_characters', $settings['diagnostic_max_characters']) }}" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"><span class="mt-1 block text-xs font-normal text-slate-500">Valor recomendado: 500 para evitar prompts demasiado extensos.</span></label>
            </div>
        </section>

        <div class="flex justify-end"><button class="rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-700">Guardar configuración</button></div>
    </form>
</div>
@endsection
