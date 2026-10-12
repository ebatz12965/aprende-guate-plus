{{-- Lección en diapositivas ($lesson, $practiceType). Las lecciones anteriores a la versión 2 se muestran como texto. --}}
@php
    $content = $lesson->content ?? [];
@endphp
@if (($content['version'] ?? 1) < 2)
    @include('adaptive.partials.lesson-document', ['lesson' => $lesson, 'practiceType' => $practiceType ?? null])
@else
    @php
        $paragraphs = fn (?string $text) => array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', trim((string) $text))), fn ($part) => $part !== ''));
        $unnumber = fn (string $text) => preg_replace('/^\s*(\d+|[A-Za-z])[.)]\s+/u', '', $text);
        // Las explicaciones antiguas nombran las opciones por índice («la opción 0»); en pantalla son letras.
        $letters = fn (string $text) => preg_match('/opci[oó]n\s+0\b/iu', $text) ? preg_replace_callback('/(opci[oó]n(?:es)?\s+)(\d)\b/iu', fn ($match) => $match[1].chr(65 + (int) $match[2]), $text) : $text;
        $sections = collect($content['sections'] ?? [])->map(fn ($section) => ['heading' => $unnumber((string) ($section['heading'] ?? '')), 'paragraphs' => $paragraphs($section['explanation'] ?? ''), 'example' => $section['example'] ?? ''])->values()->all();

        // Material del ejemplo resuelto: tabla si es CSV de una práctica de datos, código o texto.
        $worked = null;
        if (filled(data_get($content, 'worked_example.scenario'))) {
            $raw = trim((string) data_get($content, 'worked_example.material', ''));
            $material = ['kind' => 'none'];
            if ($raw !== '' && ($practiceType ?? null) === 'excel_dataset' && str_contains($raw, ',')) {
                $lines = array_values(array_filter(preg_split('/\r\n|\n|\r/', $raw), fn ($line) => trim($line) !== ''));
                $material = ['kind' => 'table', 'header' => str_getcsv(array_shift($lines)), 'rows' => array_map('str_getcsv', $lines)];
            } elseif ($raw !== '') {
                $material = ['kind' => in_array($practiceType ?? null, ['code_bug', 'code_complete'], true) ? 'code' : 'text', 'text' => $raw];
            }
            $worked = ['scenario' => data_get($content, 'worked_example.scenario'), 'material' => $material, 'steps' => array_map(fn ($step) => ['action' => $unnumber($step['action'] ?? ''), 'explanation' => $step['explanation'] ?? ''], array_values(data_get($content, 'worked_example.steps', []))), 'result' => data_get($content, 'worked_example.result', '')];
        }

        // Extracto animado de la biblioteca de Office: se consulta en vivo, así llega aunque se haya preparado después de la lección.
        $libraryTopic = isset($content['library_topic_id']) ? \App\Models\LibraryTopic::find($content['library_topic_id']) : null;
        $walkthrough = $libraryTopic?->hasWalkthrough() ? $libraryTopic->walkthrough : null;

        $decision = data_get($content, 'decision');
        $decision = $decision && count($decision['choices'] ?? []) >= 2 ? ['situation' => $decision['situation'] ?? '', 'question' => $decision['question'] ?? '¿Qué harías?', 'choices' => array_values($decision['choices'])] : null;
        $questions = collect($content['check_questions'] ?? [])->map(fn ($question) => ['question' => $question['question'] ?? '', 'options' => array_values($question['options'] ?? []), 'answer' => (int) ($question['answer'] ?? 0), 'explanation' => $letters((string) ($question['explanation'] ?? ''))])->values()->all();

        $deck = [
            'objectives' => array_values($content['objectives'] ?? []),
            'sections' => $sections,
            'concepts' => array_values($content['key_concepts'] ?? []),
            'worked' => $worked,
            'mistakes' => array_values($content['common_mistakes'] ?? []),
            'decision' => $decision,
            'questions' => $questions,
        ];

        // Orden de las diapositivas. Con dos o más secciones, la persona elige en un menú por cuál empezar.
        $order = ['cover'];
        $order = array_merge($order, count($sections) >= 2 ? ['hub'] : (count($sections) ? ['section'] : []));
        foreach (['concepts' => count($deck['concepts']), 'demo' => (bool) $walkthrough, 'worked' => (bool) $worked, 'mistakes' => count($deck['mistakes']), 'decision' => (bool) $decision] as $slide => $present) {
            if ($present) {
                $order[] = $slide;
            }
        }
        foreach (array_keys($questions) as $index) {
            $order[] = 'q'.$index;
        }
        $order[] = 'end';
    @endphp

    @php
        $saved = data_get($topic ?? null, 'metadata.lesson_progress');
        $saved = $saved && (int) ($saved['lesson_id'] ?? 0) === $lesson->id ? $saved : null;
        $labels = collect($order)->map(fn ($slide) => match (true) {
            $slide === 'cover' => 'Portada',
            $slide === 'hub' => 'Elige por dónde empezar',
            $slide === 'section' => $sections[0]['heading'] ?? 'Contenido',
            $slide === 'concepts' => 'Conceptos clave',
            $slide === 'demo' => 'Míralo en acción',
            $slide === 'worked' => 'Ejemplo resuelto',
            $slide === 'mistakes' => 'Errores frecuentes',
            $slide === 'decision' => '¿Qué harías?',
            str_starts_with($slide, 'q') => 'Pregunta '.((int) substr($slide, 1) + 1).' de '.count($questions),
            default => 'Cierre',
        })->all();
    @endphp
    @if ($walkthrough)
        @include('adaptive.partials.walkthrough-script')
    @endif
    <div x-data="lessonDeck(@js($deck), @js($order), 'ag-lesson-{{ $lesson->id }}', { lessonId: {{ $lesson->id }}, saved: @js($saved), labels: @js($labels), url: @js(isset($topic) ? route('adaptive.topic.lesson.progress', $topic) : null) })"
        x-init="init()" @keydown.window="keys($event)" class="text-slate-700">
        {{-- Al volver a una lección empezada: continuar o empezar de nuevo. --}}
        <section x-show="prompt" x-cloak class="rounded-2xl border border-blue-200 bg-blue-50/60 p-5 sm:p-8 dark:bg-blue-950/30" aria-labelledby="resume-title-{{ $lesson->id }}">
            <h3 id="resume-title-{{ $lesson->id }}" tabindex="-1" data-resume-title class="text-balance text-2xl font-extrabold text-slate-900 outline-none"
                x-text="finished ? 'Ya completaste esta lección' : 'Tienes esta lección a medias'"></h3>
            <p class="mt-2 leading-7 text-slate-700" x-show="! finished">Te quedaste en la diapositiva <span class="font-bold tabular-nums" x-text="(i + 1) + ' de ' + order.length"></span>: <span class="font-bold" x-text="labels[i]"></span>.</p>
            <p class="mt-2 leading-7 text-slate-700" x-show="finished && deck.questions.length" x-text="'Acertaste ' + score() + ' de ' + deck.questions.length + ' preguntas.'"></p>
            <div class="mt-4 h-2 max-w-md rounded-full bg-white dark:bg-slate-800" role="progressbar" aria-label="Avance de la lección" aria-valuemin="1" :aria-valuemax="order.length" :aria-valuenow="i + 1">
                <div class="h-full rounded-full bg-blue-500" :style="'width:' + ((i + 1) / order.length * 100) + '%'"></div>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" @click="resume()" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700"
                    x-text="finished ? 'Ver el cierre' : 'Continuar donde lo dejé'"></button>
                <button type="button" @click="startOver()" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-400"
                    x-text="finished ? 'Repasar desde el inicio' : 'Empezar desde el inicio'"></button>
            </div>
            <p class="mt-3 text-sm text-slate-600" x-show="! finished">Empezar de nuevo borra tus respuestas de esta lección.</p>
        </section>

        <div x-show="! prompt" class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-semibold tabular-nums text-slate-600" aria-live="polite"><span x-text="'Diapositiva ' + (i + 1) + ' de ' + order.length"></span></p>
            <button type="button" @click="asText = ! asText; save()" class="inline-flex min-h-11 items-center text-sm font-bold text-blue-700 underline-offset-4 hover:underline"
                x-text="asText ? 'Ver como diapositivas' : 'Ver como texto'">Ver como texto</button>
        </div>
        <div x-show="! prompt" class="mt-2 h-1.5 rounded-full bg-slate-100" aria-hidden="true"><div class="h-full rounded-full bg-blue-500 transition-[width] duration-300 ease-out" :style="'width:' + ((i + 1) / order.length * 100) + '%'"></div></div>

        {{-- Versión en texto: la misma lección completa en una sola página. --}}
        <div x-show="asText && ! prompt" x-cloak class="mt-6">@include('adaptive.partials.lesson-document', ['lesson' => $lesson, 'practiceType' => $practiceType ?? null])</div>

        <div x-show="! asText && ! prompt" class="mt-6 min-h-[24rem] rounded-2xl border border-slate-200 bg-slate-50/60 p-5 sm:p-8">
            {{-- Portada --}}
            <section x-show="slide === 'cover'" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                <h3 tabindex="-1" data-slide-title class="text-balance text-2xl font-extrabold text-slate-900 outline-none">{{ $topicTitle ?? 'Lección' }}</h3>
                <p class="mt-4 max-w-2xl text-lg leading-8">{{ $content['intro'] ?? '' }}</p>
                <template x-if="deck.objectives.length">
                    <div class="mt-6">
                        <p class="font-extrabold text-slate-900">Al terminar podrás</p>
                        <ul class="mt-3 space-y-2">
                            <template x-for="objective in deck.objectives"><li class="flex gap-3 leading-7"><svg class="mt-1.5 h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg><span x-text="objective"></span></li></template>
                        </ul>
                    </div>
                </template>
            </section>

            {{-- Menú: la persona elige qué parte ver primero --}}
            <section x-show="slide === 'hub' && open === null" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                <h3 tabindex="-1" data-slide-title class="text-2xl font-extrabold text-slate-900 outline-none">Elige por dónde empezar</h3>
                <p class="mt-2 leading-7 text-slate-600">Explora las <span x-text="deck.sections.length"></span> partes en el orden que prefieras. Cuando termines todas, podrás continuar.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <template x-for="(section, index) in deck.sections" :key="index">
                        <button type="button" @click="openSection(index)" class="group flex min-h-[5.5rem] items-start gap-4 rounded-2xl border bg-white p-4 text-left transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md"
                            :class="visited.includes(index) ? 'border-emerald-300' : 'border-slate-200'">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-sm font-extrabold" :class="visited.includes(index) ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'">
                                <span x-show="! visited.includes(index)" x-text="String.fromCharCode(65 + index)"></span>
                                <svg x-show="visited.includes(index)" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            </span>
                            <span><span class="block font-bold leading-6 text-slate-900" x-text="section.heading"></span><span class="mt-1 block text-sm text-slate-600" x-text="visited.includes(index) ? 'Ya la viste · toca para repasar' : 'Toca para abrir'"></span></span>
                        </button>
                    </template>
                </div>
                <p x-show="visited.length < deck.sections.length" class="mt-5 text-sm font-semibold text-slate-600" x-text="'Te faltan ' + (deck.sections.length - visited.length) + ' de ' + deck.sections.length"></p>
            </section>

            {{-- Una sección (desde el menú o, si solo hay una, en la secuencia) --}}
            <template x-if="(slide === 'hub' && open !== null) || slide === 'section'">
                <section x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                    <p x-show="slide === 'hub'" class="text-sm font-semibold text-blue-700" x-text="'Parte ' + String.fromCharCode(65 + current) + ' de ' + deck.sections.length"></p>
                    <h3 tabindex="-1" data-slide-title class="text-balance text-2xl font-extrabold text-slate-900 outline-none" x-text="deck.sections[current].heading"></h3>
                    <template x-for="paragraph in deck.sections[current].paragraphs"><p class="mt-4 max-w-2xl leading-7" x-text="paragraph"></p></template>
                    <template x-if="deck.sections[current].example">
                        <div class="mt-6">
                            <button type="button" x-show="! examples.includes(current)" @click="examples.push(current); save()"
                                class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-blue-200 bg-white px-4 text-sm font-bold text-blue-700 transition hover:border-blue-400">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Ver un ejemplo</button>
                            <p x-show="examples.includes(current)" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" class="max-w-2xl rounded-xl bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-950 dark:text-blue-50"><span class="font-bold">Ejemplo: </span><span x-text="deck.sections[current].example"></span></p>
                        </div>
                    </template>
                    <div x-show="slide === 'hub'" class="mt-8 flex flex-wrap gap-3">
                        <button type="button" @click="closeSection()" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-400">Volver al menú</button>
                        <button type="button" x-show="nextUnvisited() !== null" @click="openSection(nextUnvisited())" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700"
                            x-text="'Ir a la parte ' + String.fromCharCode(65 + (nextUnvisited() ?? 0))"></button>
                    </div>
                </section>
            </template>

            {{-- Conceptos clave: tarjetas que se voltean --}}
            <section x-show="slide === 'concepts'" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                <h3 tabindex="-1" data-slide-title class="text-2xl font-extrabold text-slate-900 outline-none">Conceptos clave</h3>
                <p class="mt-2 leading-7 text-slate-600">Antes de voltear cada tarjeta, intenta recordar qué significa.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <template x-for="(concept, index) in deck.concepts" :key="index">
                        <button type="button" @click="toggle('flipped', index)" :aria-expanded="flipped.includes(index)"
                            class="min-h-[6rem] rounded-2xl border p-4 text-left transition hover:border-blue-400" :class="flipped.includes(index) ? 'border-blue-300 bg-blue-50 dark:bg-blue-950/40' : 'border-slate-200 bg-white'">
                            <span class="block font-bold text-slate-900" x-text="concept.term"></span>
                            <span x-show="! flipped.includes(index)" class="mt-2 block text-sm text-blue-700">Toca para ver la definición</span>
                            <span x-show="flipped.includes(index)" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" class="mt-2 block text-sm leading-6" x-text="concept.definition"></span>
                        </button>
                    </template>
                </div>
            </section>

            {{-- Extracto animado (biblioteca de Office) --}}
            @if ($walkthrough)
                <template x-if="slide === 'demo'">
                    <section x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                        @include('adaptive.partials.walkthrough', [
                            'walkthrough' => $walkthrough,
                            'audio' => collect($walkthrough['steps'])->map(fn ($step, $index) => ! empty($step['audio']) ? route('library.narration', [$libraryTopic, $index + 1]) : null)->all(),
                            'tabs' => \App\Services\Library\WalkthroughService::TABS[$walkthrough['app']] ?? [],
                        ])
                    </section>
                </template>
            @endif

            {{-- Ejemplo resuelto: los pasos se revelan uno por uno --}}
            <template x-if="slide === 'worked' && deck.worked">
                <section x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                    <h3 tabindex="-1" data-slide-title class="text-2xl font-extrabold text-slate-900 outline-none">Ejemplo resuelto</h3>
                    <p class="mt-3 max-w-2xl leading-7" x-text="deck.worked.scenario"></p>
                    <template x-if="deck.worked.material.kind === 'table'">
                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                            <table class="w-full text-left text-sm tabular-nums">
                                <thead class="bg-slate-50"><tr><template x-for="cell in deck.worked.material.header"><th scope="col" class="whitespace-nowrap px-3 py-2 font-bold text-slate-900" x-text="cell"></th></template></tr></thead>
                                <tbody class="divide-y divide-slate-200"><template x-for="row in deck.worked.material.rows"><tr><template x-for="cell in row"><td class="whitespace-nowrap px-3 py-2" x-text="cell"></td></template></tr></template></tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="deck.worked.material.kind === 'code'"><pre class="mt-4 overflow-auto rounded-xl bg-slate-950 p-4 text-sm leading-6 text-slate-100"><code x-text="deck.worked.material.text"></code></pre></template>
                    <template x-if="deck.worked.material.kind === 'text'"><p class="mt-4 whitespace-pre-line rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6" x-text="deck.worked.material.text"></p></template>
                    <ol class="mt-5 space-y-3">
                        <template x-for="(step, index) in deck.worked.steps" :key="index">
                            <li x-show="index < steps" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0" class="flex gap-4">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-blue-100 text-sm font-extrabold tabular-nums text-blue-800" x-text="index + 1"></span>
                                <div class="leading-7"><p class="font-bold text-slate-900" x-text="step.action"></p><p class="text-sm leading-6 text-slate-600" x-text="step.explanation"></p></div>
                            </li>
                        </template>
                    </ol>
                    <button type="button" x-show="steps < deck.worked.steps.length" @click="steps++; save()"
                        class="mt-5 inline-flex min-h-11 items-center rounded-xl border border-blue-200 bg-white px-4 text-sm font-bold text-blue-700 transition hover:border-blue-400"
                        x-text="steps === 0 ? 'Mostrar el primer paso' : 'Mostrar el paso ' + (steps + 1) + ' de ' + deck.worked.steps.length"></button>
                    <p x-show="steps >= deck.worked.steps.length" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" class="mt-5 max-w-2xl rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm leading-6"><span class="font-bold text-slate-900">Resultado: </span><span x-text="deck.worked.result"></span></p>
                </section>
            </template>

            {{-- Errores frecuentes: se toca el error para ver cómo evitarlo --}}
            <section x-show="slide === 'mistakes'" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                <h3 tabindex="-1" data-slide-title class="text-2xl font-extrabold text-slate-900 outline-none">Errores frecuentes</h3>
                <p class="mt-2 leading-7 text-slate-600">Toca cada error para ver cómo evitarlo.</p>
                <div class="mt-6 space-y-3">
                    <template x-for="(mistake, index) in deck.mistakes" :key="index">
                        <button type="button" @click="toggle('fixes', index)" :aria-expanded="fixes.includes(index)" class="block w-full rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-blue-400">
                            <span class="flex items-start gap-3"><svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg><span class="font-bold leading-6 text-slate-900" x-text="mistake.mistake"></span></span>
                            <span x-show="fixes.includes(index)" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" class="mt-3 flex items-start gap-3 text-sm leading-6"><svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg><span x-text="mistake.fix"></span></span>
                        </button>
                    </template>
                </div>
            </section>

            {{-- ¿Qué harías? Una decisión con consecuencias --}}
            <template x-if="slide === 'decision' && deck.decision">
                <section x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                    <h3 tabindex="-1" data-slide-title class="text-2xl font-extrabold text-slate-900 outline-none">¿Qué harías?</h3>
                    <p class="mt-3 max-w-2xl leading-7" x-text="deck.decision.situation"></p>
                    <p class="mt-4 font-bold text-slate-900" x-text="deck.decision.question"></p>
                    <div class="mt-4 grid gap-3">
                        <template x-for="(choice, index) in deck.decision.choices" :key="index">
                            <button type="button" @click="decision = index; save()" :aria-pressed="decision === index"
                                class="flex min-h-11 items-start gap-3 rounded-xl border bg-white px-4 py-3 text-left text-sm leading-6 transition hover:border-blue-400"
                                :class="decision === index ? (choice.best ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40' : 'border-amber-400 bg-amber-50 dark:bg-amber-950/40') : 'border-slate-200'">
                                <span class="font-extrabold text-blue-800" x-text="String.fromCharCode(65 + index) + '.'"></span><span x-text="choice.label"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="decision !== null" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" role="status" class="mt-4 max-w-2xl rounded-xl bg-white px-4 py-3 text-sm leading-6">
                        <span class="font-bold" :class="deck.decision.choices[decision ?? 0]?.best ? 'text-emerald-700' : 'text-amber-700'" x-text="deck.decision.choices[decision ?? 0]?.best ? 'Buena decisión. ' : 'Piénsalo de nuevo. '"></span><span x-text="deck.decision.choices[decision ?? 0]?.outcome"></span>
                    </p>
                </section>
            </template>

            {{-- Preguntas: una por diapositiva --}}
            <template x-for="(question, qi) in deck.questions" :key="qi">
                <section x-show="slide === 'q' + qi" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                    <p class="text-sm font-semibold text-blue-700" x-text="'Pregunta ' + (qi + 1) + ' de ' + deck.questions.length"></p>
                    <h3 tabindex="-1" data-slide-title class="mt-1 whitespace-pre-line text-xl font-extrabold leading-8 text-slate-900 outline-none" x-text="question.question"></h3>
                    <div class="mt-5 grid gap-2">
                        <template x-for="(option, oi) in question.options" :key="oi">
                            <button type="button" @click="answer(qi, oi)" :disabled="picked[qi] !== undefined" :aria-pressed="picked[qi] === oi"
                                class="flex min-h-11 items-center gap-3 rounded-xl border px-4 py-2.5 text-left text-sm leading-6 transition"
                                :class="picked[qi] === undefined ? 'border-slate-200 bg-white hover:border-blue-400' : (oi === question.answer ? 'border-emerald-500 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-50' : (picked[qi] === oi ? 'border-rose-400 bg-rose-50 text-rose-900 dark:bg-rose-950/40 dark:text-rose-50' : 'border-slate-200 bg-white opacity-60'))">
                                <span class="font-extrabold" x-text="String.fromCharCode(65 + oi) + '.'"></span><span class="whitespace-pre-line" x-text="option"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="picked[qi] !== undefined" x-cloak role="status" class="mt-4 rounded-xl bg-white px-4 py-3 text-sm leading-6">
                        <span class="font-bold" :class="picked[qi] === question.answer ? 'text-emerald-700' : 'text-rose-700'" x-text="picked[qi] === question.answer ? 'Correcto. ' : 'No es esa. '"></span><span x-text="question.explanation"></span>
                    </p>
                </section>
            </template>

            {{-- Cierre --}}
            <section x-show="slide === 'end'" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                <div class="flex items-center gap-4">
                    <svg class="h-11 w-11 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
                    <h3 tabindex="-1" data-slide-title class="text-2xl font-extrabold text-slate-900 outline-none">Terminaste la lección</h3>
                </div>
                <p x-show="deck.questions.length" class="mt-4 text-lg font-bold tabular-nums text-slate-900" x-text="'Acertaste ' + score() + ' de ' + deck.questions.length + ' preguntas.'"></p>
                <p x-show="deck.questions.length && score() < deck.questions.length" class="mt-1 text-sm leading-6 text-slate-600">Repasa las partes de las preguntas que fallaste antes de la práctica.</p>
                <div class="mt-6 max-w-2xl rounded-2xl border border-blue-200 bg-white px-5 py-4">
                    <p class="font-extrabold text-slate-900">En resumen</p>
                    <p class="mt-2 leading-7">{{ $content['summary'] ?? '' }}</p>
                </div>
                @if (count(data_get($lesson->resources, 'youtube_queries', [])))
                    <ul class="mt-4 space-y-1">
                        @foreach (data_get($lesson->resources, 'youtube_queries', []) as $query)
                            <li><a target="_blank" rel="noopener" href="https://www.youtube.com/results?search_query={{ urlencode($query) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Buscar en YouTube: {{ $query }}<span class="sr-only"> (se abre en otra pestaña)</span></a></li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="#practica" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700">Ir a la práctica</a>
                    <button type="button" @click="restart()" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-400">Repasar desde el inicio</button>
                </div>
            </section>
        </div>

        {{-- Navegación --}}
        <div x-show="! asText && ! prompt && ! (slide === 'hub' && open !== null)" class="mt-4 flex items-center justify-between gap-3">
            <button type="button" @click="go(i - 1)" :disabled="i === 0" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-400 disabled:cursor-not-allowed disabled:opacity-40">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>Anterior</button>
            <p x-show="! canNext()" class="hidden text-center text-sm text-slate-600 sm:block" x-text="slide === 'hub' ? 'Explora todas las partes para continuar' : 'Responde para continuar'"></p>
            <button type="button" x-show="slide !== 'end'" @click="go(i + 1)" :disabled="! canNext()" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40">
                <span x-text="i === 0 ? 'Comenzar' : 'Siguiente'"></span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
        </div>
    </div>

    @once
        <script nonce="{{ Vite::cspNonce() }}">
            function lessonDeck(deck, order, storageKey, sync = {}) {
                return {
                    deck, order, storageKey, rootEl: null, i: 0, open: null, current: 0, visited: [], examples: [], flipped: [], fixes: [], steps: 0, decision: null, picked: {}, asText: false,
                    labels: sync.labels || [], prompt: false, timer: null,
                    get finished() { return this.i === this.order.length - 1; },
                    get slide() { return this.order[this.i]; },
                    init() {
                        this.rootEl = this.$el;
                        // El avance más reciente gana: el del servidor (cualquier dispositivo) o el de este navegador.
                        let local = {};
                        try { local = JSON.parse(localStorage.getItem(this.storageKey) || '{}'); } catch (error) {}
                        const server = sync.saved || {};
                        const saved = (server.saved_at || 0) >= (local.saved_at || 0) ? server : local;
                        ['visited', 'examples', 'flipped', 'fixes'].forEach((key) => { if (Array.isArray(saved[key])) this[key] = saved[key]; });
                        if (saved.picked && typeof saved.picked === 'object') this.picked = { ...saved.picked };
                        this.steps = saved.steps || 0; this.decision = saved.decision ?? null; this.asText = !! saved.asText;
                        this.i = Math.min(Math.max(0, saved.i || 0), this.order.length - 1);
                        if (this.slide === 'section') this.current = 0;
                        // Si ya avanzó, se pregunta si continuar o empezar de nuevo.
                        this.prompt = this.i > 0;
                        if (this.prompt) this.$nextTick(() => this.rootEl.querySelector('[data-resume-title]')?.focus({ preventScroll: true }));
                    },
                    snapshot() {
                        return { lesson_id: sync.lessonId, i: this.i, visited: this.visited, examples: this.examples, flipped: this.flipped, fixes: this.fixes, steps: this.steps, decision: this.decision, picked: this.picked, asText: this.asText, saved_at: Date.now() };
                    },
                    save() {
                        const state = this.snapshot();
                        try { localStorage.setItem(this.storageKey, JSON.stringify(state)); } catch (error) {}
                        // Al servidor con una pausa corta, para no enviar una petición por cada clic.
                        if (! sync.url) return;
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => {
                            const { saved_at, ...body } = state;
                            fetch(sync.url, { method: 'PUT', keepalive: true, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, body: JSON.stringify(body) }).catch(() => {});
                        }, 800);
                    },
                    resume() { this.prompt = false; if (this.slide === 'section') this.current = 0; this.focus(); },
                    startOver() {
                        Object.assign(this, { visited: [], examples: [], flipped: [], fixes: [], steps: 0, decision: null, picked: {}, open: null, current: 0, i: 0, prompt: false });
                        this.save(); this.focus();
                    },
                    canNext() {
                        if (this.slide === 'hub') return this.visited.length >= this.deck.sections.length;
                        if (this.slide.startsWith('q')) return this.picked[Number(this.slide.slice(1))] !== undefined;
                        return true;
                    },
                    go(index) {
                        if (index < 0 || index >= this.order.length || (index > this.i && ! this.canNext())) return;
                        this.i = index; this.open = null;
                        if (this.slide === 'section') { this.current = 0; if (! this.visited.includes(0)) this.visited.push(0); }
                        this.save(); this.focus();
                    },
                    openSection(index) { this.open = index; this.current = index; if (! this.visited.includes(index)) this.visited.push(index); this.save(); this.focus(); },
                    closeSection() { this.open = null; this.focus(); },
                    nextUnvisited() { const next = this.deck.sections.findIndex((section, index) => ! this.visited.includes(index)); return next === -1 ? null : next; },
                    toggle(key, index) { this[key] = this[key].includes(index) ? this[key].filter((item) => item !== index) : [...this[key], index]; this.save(); },
                    answer(question, option) { if (this.picked[question] === undefined) { this.picked = { ...this.picked, [question]: option }; this.save(); } },
                    score() { return Object.entries(this.picked).filter(([question, option]) => this.deck.questions[question]?.answer === option).length; },
                    restart() { this.startOver(); },
                    focus() { this.$nextTick(() => [...this.rootEl.querySelectorAll('[data-slide-title]')].find((title) => title.offsetParent !== null)?.focus({ preventScroll: true })); },
                    keys(event) {
                        // Las flechas solo navegan si el foco está en la lección (o en ninguna parte) y no en un campo de texto.
                        const focused = document.activeElement;
                        const inDeck = focused === document.body || this.rootEl.contains(focused);
                        // Tampoco si la pestaña de la lección está oculta.
                        if (this.asText || this.prompt || ! inDeck || this.rootEl.offsetParent === null || ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) return;
                        if (event.key === 'ArrowRight') this.go(this.i + 1);
                        if (event.key === 'ArrowLeft') this.go(this.i - 1);
                    },
                };
            }
        </script>
    @endonce
@endif
