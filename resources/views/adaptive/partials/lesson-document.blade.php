{{-- Lección del tema ($lesson). La versión 2 trae secciones, ejemplo resuelto, errores frecuentes y preguntas de comprobación. --}}
@php($content = $lesson->content ?? [])
@if (($content['version'] ?? 1) >= 2)
    <div class="max-w-3xl space-y-10 text-slate-700">
        <div>
            <p class="text-lg leading-8">{{ $content['intro'] ?? '' }}</p>
            @if (count($content['objectives'] ?? []))
                <h3 class="mt-6 font-extrabold text-slate-900">Al terminar podrás</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($content['objectives'] as $objective)
                        <li class="flex gap-3 leading-7"><svg class="mt-1.5 h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>{{ $objective }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        @foreach ($content['sections'] ?? [] as $section)
            <section>
                <h3 class="text-balance text-xl font-extrabold text-slate-900">{{ $section['heading'] ?? '' }}</h3>
                @foreach (preg_split('/\n\s*\n/', trim($section['explanation'] ?? '')) as $paragraph)
                    <p class="mt-3 leading-7">{{ $paragraph }}</p>
                @endforeach
                @if (filled($section['example'] ?? null))
                    <p class="mt-4 rounded-xl bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-950 dark:text-blue-50"><span class="font-bold">Ejemplo: </span>{{ $section['example'] }}</p>
                @endif
            </section>
        @endforeach

        @if (filled(data_get($content, 'worked_example.scenario')))
            <section>
                <h3 class="text-xl font-extrabold text-slate-900">Ejemplo resuelto</h3>
                <p class="mt-3 leading-7">{{ data_get($content, 'worked_example.scenario') }}</p>
                @php($workedMaterial = trim((string) data_get($content, 'worked_example.material', '')))
                @if ($workedMaterial !== '')
                    @if (($practiceType ?? null) === 'excel_dataset' && str_contains($workedMaterial, ','))
                        @php($workedRows = array_values(array_filter(preg_split('/\r\n|\n|\r/', $workedMaterial), fn ($line) => trim($line) !== '')))
                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-sm tabular-nums">
                                <thead class="bg-slate-50"><tr>@foreach (str_getcsv(array_shift($workedRows)) as $cell)<th scope="col" class="whitespace-nowrap px-3 py-2 font-bold text-slate-900">{{ $cell }}</th>@endforeach</tr></thead>
                                <tbody class="divide-y divide-slate-200">@foreach ($workedRows as $row)<tr>@foreach (str_getcsv($row) as $cell)<td class="whitespace-nowrap px-3 py-2">{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
                            </table>
                        </div>
                    @elseif (in_array($practiceType ?? null, ['code_bug', 'code_complete'], true))
                        <pre class="mt-4 overflow-auto rounded-xl bg-slate-950 p-4 text-sm leading-6 text-slate-100"><code>{{ $workedMaterial }}</code></pre>
                    @else
                        <p class="mt-4 whitespace-pre-line rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6">{{ $workedMaterial }}</p>
                    @endif
                @endif
                <ol class="mt-4 space-y-4">
                    @foreach (data_get($content, 'worked_example.steps', []) as $step)
                        <li class="flex gap-4">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-blue-100 text-sm font-extrabold tabular-nums text-blue-800">{{ $loop->iteration }}</span>
                            <div class="leading-7"><p class="font-bold text-slate-900">{{ $step['action'] ?? '' }}</p><p class="text-sm leading-6 text-slate-600">{{ $step['explanation'] ?? '' }}</p></div>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-4 rounded-xl border border-blue-200 px-4 py-3 text-sm leading-6"><span class="font-bold text-slate-900">Resultado: </span>{{ data_get($content, 'worked_example.result') }}</p>
            </section>
        @endif

        @if (count($content['common_mistakes'] ?? []))
            <section>
                <h3 class="text-xl font-extrabold text-slate-900">Errores frecuentes</h3>
                <ul class="mt-4 space-y-4">
                    @foreach ($content['common_mistakes'] as $mistake)
                        <li class="leading-7"><p class="font-bold text-slate-900">{{ $mistake['mistake'] ?? '' }}</p><p class="text-sm leading-6 text-slate-600">Cómo evitarlo: {{ $mistake['fix'] ?? '' }}</p></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (count($content['key_concepts'] ?? []))
            <section>
                <h3 class="text-xl font-extrabold text-slate-900">Conceptos clave</h3>
                <dl class="mt-4 divide-y divide-slate-200 border-y border-slate-200">
                    @foreach ($content['key_concepts'] as $concept)
                        <div class="grid gap-1 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4"><dt class="font-bold text-slate-900">{{ $concept['term'] ?? '' }}</dt><dd class="text-sm leading-6">{{ $concept['definition'] ?? '' }}</dd></div>
                    @endforeach
                </dl>
            </section>
        @endif

        @if (count($content['check_questions'] ?? []))
            @php($questions = collect($content['check_questions'])->map(fn ($question) => ['question' => $question['question'] ?? '', 'options' => $question['options'] ?? [], 'answer' => (int) ($question['answer'] ?? 0), 'explanation' => $question['explanation'] ?? ''])->values())
            <section x-data="{ questions: @js($questions), picked: {}, get correct() { return Object.entries(this.picked).filter(([index, option]) => this.questions[index].answer === option).length } }">
                <h3 class="text-xl font-extrabold text-slate-900">Comprueba lo que aprendiste</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Elige una respuesta en cada pregunta. Verás al momento si es correcta y por qué.</p>
                <template x-for="(question, index) in questions" :key="index">
                    <fieldset class="mt-6">
                        <legend class="font-bold leading-7 text-slate-900"><span x-text="(index + 1) + '. ' + question.question"></span></legend>
                        <div class="mt-3 grid gap-2">
                            <template x-for="(option, optionIndex) in question.options" :key="optionIndex">
                                <button type="button" @click="if (picked[index] === undefined) picked[index] = optionIndex" :disabled="picked[index] !== undefined"
                                    :aria-pressed="picked[index] === optionIndex"
                                    class="flex min-h-11 items-center gap-3 rounded-xl border px-4 py-2.5 text-left text-sm leading-6 transition"
                                    :class="picked[index] === undefined ? 'border-slate-200 bg-white hover:border-blue-400' : (optionIndex === question.answer ? 'border-emerald-500 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-50' : (picked[index] === optionIndex ? 'border-rose-400 bg-rose-50 text-rose-900 dark:bg-rose-950/40 dark:text-rose-50' : 'border-slate-200 bg-white opacity-60'))">
                                    <span x-text="option"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="picked[index] !== undefined" x-cloak role="status" class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm leading-6">
                            <span class="font-bold" :class="picked[index] === question.answer ? 'text-emerald-700' : 'text-rose-700'" x-text="picked[index] === question.answer ? 'Correcto. ' : 'No es esa. '"></span><span x-text="question.explanation"></span>
                        </p>
                    </fieldset>
                </template>
                <p x-show="Object.keys(picked).length === questions.length" x-cloak class="mt-6 font-bold text-slate-900" role="status"
                    x-text="'Acertaste ' + correct + ' de ' + questions.length + (correct === questions.length ? '. ¡Listo para la práctica!' : '. Repasa las secciones de las que fallaste antes de la práctica.')"></p>
            </section>
        @endif

        @if (filled($content['summary'] ?? null))
            <section class="rounded-2xl border border-blue-200 px-5 py-4">
                <h3 class="font-extrabold text-slate-900">En resumen</h3>
                <p class="mt-2 leading-7">{{ $content['summary'] }}</p>
            </section>
        @endif

        @if (count(data_get($lesson->resources, 'youtube_queries', [])))
            <div>
                <h3 class="font-extrabold text-slate-900">Videos para reforzar</h3>
                <ul class="mt-2 space-y-1">
                    @foreach (data_get($lesson->resources, 'youtube_queries', []) as $query)
                        <li><a target="_blank" rel="noopener" href="https://www.youtube.com/results?search_query={{ urlencode($query) }}"
                                class="inline-flex min-h-11 items-center text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Buscar en YouTube: {{ $query }}<span class="sr-only"> (se abre en otra pestaña)</span></a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@else
    {{-- Lecciones generadas antes de la versión 2. --}}
    <div class="max-w-3xl text-slate-700">
        <p class="leading-7">{{ $content['summary'] ?? '' }}</p>
        <h3 class="mt-5 font-extrabold text-slate-900">Conceptos clave</h3>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
            @foreach ($content['key_concepts'] ?? [] as $concept)
                <li>{{ is_array($concept) ? ($concept['term'] ?? '') : $concept }}</li>
            @endforeach
        </ul>
        <h3 class="mt-5 font-extrabold text-slate-900">Proceso sugerido</h3>
        <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm">
            @foreach ($content['steps'] ?? [] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>
        <div class="mt-5 flex flex-wrap gap-x-5">
            @foreach (data_get($lesson->resources, 'youtube_queries', []) as $query)
                <a target="_blank" rel="noopener" href="https://www.youtube.com/results?search_query={{ urlencode($query) }}"
                    class="inline-flex min-h-11 items-center text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Buscar en YouTube: {{ $query }}</a>
            @endforeach
        </div>
    </div>
@endif
