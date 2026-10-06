@extends('layouts.platform')

@section('content')
@use('App\Models\InterviewSimulation')
@use('App\Models\InterviewStage')
@php($field = 'mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500')
<div class="mx-auto max-w-6xl">
    <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl sm:px-9">
        <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-blue-400/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative max-w-3xl">
            <h1 class="text-3xl font-extrabold">Empleabilidad</h1>
            <p class="mt-2 leading-7 text-slate-300">Simulador de postulación: practica el proceso de selección de un puesto antes de vivirlo: una entrevista con Recursos Humanos, una prueba de razonamiento, una entrevista con quien dirige el área y una prueba práctica. Al final sabrás qué reforzar.</p>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
        <section class="learning-panel" aria-labelledby="nueva">
            <h2 id="nueva" class="text-xl font-extrabold text-slate-900">Prepara una postulación</h2>
            <p class="mt-1 text-sm text-slate-600">Te quedan {{ $remainingToday }} {{ $remainingToday === 1 ? 'simulación' : 'simulaciones' }} por hoy.</p>
            <form method="POST" action="{{ route('adaptive.interviews.store') }}" class="mt-5 space-y-5">
                @csrf
                <div>
                    <label for="job_title" class="block text-sm font-semibold text-slate-700">Puesto al que quieres aplicar</label>
                    <input id="job_title" name="job_title" type="text" maxlength="160" required value="{{ old('job_title', $suggestedTitle) }}" placeholder="Por ejemplo: Técnico de soporte de TI" class="{{ $field }}">
                    @if ($suggestedTitle && ! old('job_title'))<p class="mt-1 text-xs text-slate-500">Lo tomamos de la meta de tu diagnóstico; puedes cambiarlo.</p>@endif
                    @error('job_title')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="area" class="block text-sm font-semibold text-slate-700">Área</label>
                        <select id="area" name="area" required class="{{ $field }}">
                            @foreach (InterviewSimulation::AREAS as $value => $label)
                                <option value="{{ $value }}" @selected(old('area') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="seniority" class="block text-sm font-semibold text-slate-700">Tu experiencia</label>
                        <select id="seniority" name="seniority" required class="{{ $field }}">
                            @foreach (InterviewSimulation::SENIORITY as $value => $label)
                                <option value="{{ $value }}" @selected(old('seniority', 'junior') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label for="job_description" class="block text-sm font-semibold text-slate-700">Texto de la vacante <span class="font-normal text-slate-500">(opcional)</span></label>
                    <textarea id="job_description" name="job_description" rows="4" maxlength="6000" class="{{ $field }}" placeholder="Pega aquí la publicación del empleo, si la tienes: requisitos, funciones, horario.">{{ old('job_description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Con la vacante, las preguntas se parecen más a las de esa empresa.</p>
                    @error('job_description')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-blue-800 disabled:opacity-50" @disabled($remainingToday <= 0)>Preparar el proceso</button>
            </form>
        </section>

        <aside class="learning-panel" aria-labelledby="como">
            <h2 id="como" class="text-xl font-extrabold text-slate-900">Cómo funciona</h2>
            <ol class="mt-4 space-y-4">
                @foreach (InterviewStage::TYPES as $type => $label)
                    <li class="flex gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-blue-50 text-sm font-extrabold text-blue-800">{{ $loop->iteration }}</span>
                        <div>
                            <p class="font-bold text-slate-900">{{ $label }}</p>
                            <p class="text-sm leading-6 text-slate-600">{{ match ($type) {
                                'hr_interview' => 'Una conversación escrita sobre tu motivación, cómo trabajas en equipo y qué esperas del puesto.',
                                'reasoning_test' => 'Lógica, números, lectura y situaciones del trabajo. Es práctica, no un test psicométrico validado.',
                                'technical_interview' => 'Preguntas técnicas del puesto con quien sería tu jefe o jefa; repregunta según lo que respondes.',
                                default => 'Un caso corto del puesto que resuelves en computadora y entregas por escrito o en un archivo.',
                            } }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="mt-5 rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-600">La empresa y las personas entrevistadoras son ficticias. Nunca te preguntarán por tu edad, religión, estado civil, hijos, origen ni otros datos personales que no tienen relación con el trabajo.</p>
        </aside>
    </div>

    <section class="mt-8" aria-labelledby="tus">
        <h2 id="tus" class="text-xl font-extrabold text-slate-900">Tus simulaciones</h2>
        @if ($simulations->isEmpty())
            <p class="mt-3 text-slate-600">Todavía no has preparado ninguna. Empieza con el puesto que más te interesa.</p>
        @else
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($simulations as $simulation)
                    <a href="{{ route('adaptive.interviews.show', $simulation) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-extrabold text-slate-900">{{ $simulation->job_title }}</p>
                                <p class="mt-1 truncate text-sm text-slate-600">{{ $simulation->company['name'] ?? $simulation->areaLabel() }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold {{ match ($simulation->status) {
                                'generating' => 'bg-blue-50 text-blue-800',
                                'failed' => 'bg-rose-50 text-rose-800',
                                'completed' => 'bg-emerald-50 text-emerald-800',
                                default => 'bg-slate-100 text-slate-700',
                            } }}">{{ match ($simulation->status) {
                                'generating' => 'Preparando',
                                'failed' => 'Sin preparar',
                                'completed' => 'Terminada',
                                default => $simulation->finished_stages_count.' de '.$simulation->stages_count.' etapas',
                            } }}</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">{{ $simulation->seniorityLabel() }} · creada {{ $simulation->created_at->diffForHumans() }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
