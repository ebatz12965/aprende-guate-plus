@extends('layouts.platform')
@section('content')
    @php($analysis = $attempt->analysis ?? [])
    @php($questions = $attempt->questions ?? [])
    @php($graded = collect($questions)->where('kind', 'knowledge')->count())
    <div class="mx-auto max-w-3xl pb-20" x-data="{ total: {{ count($questions) }}, answered: 0, count() { this.answered = new Set([...this.$root.querySelectorAll('input[type=radio]:checked')].map((input) => input.name)).size } }"
        x-init="count()" @change="count()">
        <h1 class="text-balance text-3xl font-extrabold text-slate-900">Midamos desde dónde empiezas</h1>
        <p class="mt-2 max-w-2xl leading-7 text-slate-600">Con tus respuestas armamos una ruta que refuerza lo que te falta y no repite lo que ya sabes.</p>

        @if ($analysis)
            <section class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-950 dark:text-blue-50">
                <h2 class="text-lg font-extrabold">Esto entendimos de tu meta</h2>
                <p class="mt-2 leading-7">{{ $analysis['summary'] ?? $attempt->primary_answer }}</p>
                @if (filled($analysis['target_role'] ?? null))
                    <p class="mt-2 text-sm leading-6"><span class="font-bold">Objetivo: </span>{{ $analysis['target_role'] }}</p>
                @endif
                @if (count($analysis['skills'] ?? []))
                    <p class="mt-3 text-sm font-bold">Habilidades que vamos a medir</p>
                    <ul class="mt-1 grid gap-x-6 gap-y-1 text-sm leading-6 sm:grid-cols-2">
                        @foreach ($analysis['skills'] as $skill)
                            <li class="flex gap-2"><span aria-hidden="true">·</span>{{ $skill['name'] }}</li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('adaptive.diagnostic', ['meta' => $attempt->primary_answer]) }}"
                    class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 underline underline-offset-4 hover:text-blue-900">¿No es así? Ajusta tu meta</a>
            </section>
        @endif

        <form method="POST" action="{{ route('adaptive.diagnostic.store', $attempt) }}" class="mt-8 space-y-6">@csrf
            @if ($graded)
                <p class="text-sm leading-6 text-slate-600">Son {{ count($questions) }} preguntas, unos {{ max(5, (int) ceil(count($questions) * 0.6)) }} minutos. Si no sabes una respuesta, elige «No lo sé»: adivinar hace que tu ruta empiece donde no te corresponde.</p>
            @endif
            @foreach ($questions as $index => $question)
                @php($kind = $question['kind'] ?? 'context')
                <fieldset class="learning-panel">
                    <legend class="sr-only">Pregunta {{ $index + 1 }}</legend>
                    @if ($kind !== 'context' && filled($question['skill'] ?? null))
                        <p class="text-sm font-semibold text-blue-700">{{ $question['skill'] }}</p>
                    @endif
                    <p class="mt-1 whitespace-pre-line font-bold leading-7 text-slate-900">{{ $index + 1 }}. {{ $question['question'] ?? '' }}</p>
                    <div class="mt-4 grid gap-2">
                        @foreach ($question['options'] ?? [] as $optionIndex => $option)
                            <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 text-sm leading-6 text-slate-700 transition hover:border-blue-400 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input required class="h-4 w-4 shrink-0 border-slate-300 text-blue-600 focus:ring-blue-500" type="radio" name="answers[{{ $index }}]" value="{{ $optionIndex }}" @checked((string) old("answers.$index") === (string) $optionIndex)>
                                <span class="whitespace-pre-line">{{ $option }}</span>
                            </label>
                        @endforeach
                        @if ($kind === 'knowledge')
                            <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-3 py-2.5 text-sm leading-6 text-slate-600 transition hover:border-blue-400 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input required class="h-4 w-4 shrink-0 border-slate-300 text-blue-600 focus:ring-blue-500" type="radio" name="answers[{{ $index }}]" value="{{ \App\Services\Learning\DiagnosticQuestionService::DONT_KNOW }}" @checked((string) old("answers.$index") === (string) \App\Services\Learning\DiagnosticQuestionService::DONT_KNOW)>
                                No lo sé
                            </label>
                        @endif
                    </div>
                </fieldset>
            @endforeach

            <div class="sticky bottom-[5.5rem] z-10 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between dark:bg-slate-900/95">
                <p class="text-sm font-semibold tabular-nums text-slate-700" role="status"><span x-text="answered">0</span> de {{ count($questions) }} respondidas</p>
                <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="answered < total">Ver mi nivel y crear mi ruta</button>
            </div>
        </form>
    </div>
@endsection
