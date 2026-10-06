@extends('layouts.platform')

@section('content')
@php
    $initials = collect(explode(' ', $stage->interviewer['name'] ?? ''))->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('');
    $tips = match ($stage->type) {
        'hr_interview' => ['Responde con ejemplos concretos: la situación, lo que tenías que hacer, lo que hiciste y el resultado (método STAR).', 'Lee la descripción de la empresa ficticia; te preguntarán por qué quieres trabajar ahí.', 'Prepara una o dos preguntas para hacerle a la empresa al final.'],
        'reasoning_test' => ['Lee cada pregunta completa antes de responder.', 'Si una pregunta te traba, sigue con la próxima y vuelve después.', 'En las situaciones de trabajo no hay trampa: elige lo que de verdad harías.'],
        'technical_interview' => ['Explica cómo llegas a tu respuesta, no solo la respuesta.', 'Si no sabes algo, dilo y explica cómo lo averiguarías: eso también cuenta.', 'Usa ejemplos de tus prácticas o de trabajos anteriores.'],
        default => ['Lee el caso y el entregable completos antes de empezar.', 'Organiza tu respuesta en pasos y justifica tus decisiones.', 'Revisa tu entrega antes de enviarla: cuenta como en una prueba real.'],
    };
@endphp
<div class="mx-auto max-w-3xl">
    <a href="{{ route('adaptive.interviews.show', $simulation) }}" class="inline-flex min-h-10 items-center text-sm font-bold text-blue-700 hover:text-blue-900">← {{ $simulation->job_title }}</a>

    <section class="mt-4 learning-panel">
        <p class="text-sm font-semibold text-slate-500">Etapa {{ $stage->position }} de 4 · {{ $stage->duration_minutes }} minutos</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stage->title }}</h1>

        @if ($stage->interviewer)
            <div class="mt-5 flex items-start gap-4 rounded-2xl bg-slate-50 p-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-blue-100 font-extrabold text-blue-800" aria-hidden="true">{{ $initials }}</span>
                <div>
                    <p class="font-extrabold text-slate-900">{{ $stage->interviewer['name'] }}</p>
                    <p class="text-sm text-slate-600">{{ $stage->interviewer['role'] }} · {{ $simulation->company['name'] ?? '' }}</p>
                    @if (! empty($stage->interviewer['style']))<p class="mt-2 text-sm leading-6 text-slate-600">{{ $stage->interviewer['style'] }}</p>@endif
                </div>
            </div>
        @endif

        <p class="mt-5 leading-7 text-slate-700">{{ $stage->purpose }}</p>

        @if ($stage->focus)
            <h2 class="mt-6 font-extrabold text-slate-900">{{ $stage->type === 'practical_test' ? 'Qué entregarás' : 'Qué se evalúa' }}</h2>
            <ul class="mt-2 space-y-1.5">
                @foreach ($stage->focus as $topic)
                    <li class="flex gap-2 text-slate-700"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-500" aria-hidden="true"></span>{{ $topic }}</li>
                @endforeach
            </ul>
        @endif

        <h2 class="mt-6 font-extrabold text-slate-900">Consejos</h2>
        <ul class="mt-2 space-y-1.5">
            @foreach ($tips as $tip)
                <li class="flex gap-2 text-slate-700"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>{{ $tip }}</li>
            @endforeach
        </ul>

        <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-6">
            <button type="button" disabled class="inline-flex min-h-11 cursor-not-allowed items-center rounded-xl bg-slate-200 px-5 text-sm font-bold text-slate-500">Comenzar</button>
            <p class="text-sm text-slate-500">Esta etapa se habilita en la próxima actualización del simulador.</p>
        </div>
    </section>
</div>
@endsection
