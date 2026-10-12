{{--
    Prueba de razonamiento en curso, en diapositivas como las lecciones: una pregunta por pantalla, mapa para saltar
    a cualquiera y una revisión final antes de entregar. Todas las respuestas viven en un solo formulario (las
    diapositivas solo se ocultan), así que el envío y la entrega automática al terminar el tiempo no cambian.
    Las respuestas correctas no se envían al navegador.
--}}
@php
    $categories = \App\Services\Interviews\ReasoningTestService::CATEGORIES;
    // En el orden de las secciones: lógica, números, lectura y situaciones.
    $items = collect($stage->content['items'] ?? [])->sortBy(fn ($item) => array_search($item['category'] ?? '', array_keys($categories), true))->values();
    $deadline = $stage->deadline();
    $slides = $items->map(fn ($item) => ['id' => $item['id'], 'category' => $categories[$item['category']] ?? ''])->all();
@endphp
<form method="POST" action="{{ route('adaptive.interviews.stage.submit', [$simulation, $stage]) }}" class="mt-6"
    x-data="reasoningTest({ deadline: @js($deadline?->toIso8601String()), slides: @js($slides), key: 'ag-reasoning-{{ $stage->id }}' })" x-ref="form" @submit="submitting = true; clear()"
    @keydown.window="keys($event)">
    @csrf
    <div class="sticky top-[76px] z-20 rounded-2xl border border-slate-200 bg-white/95 px-5 py-3 shadow-sm backdrop-blur">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-semibold tabular-nums text-slate-600" aria-live="polite" x-text="i < slides.length ? 'Pregunta ' + (i + 1) + ' de ' + slides.length : 'Revisión final'"></p>
            <p class="text-sm font-bold text-slate-700">Respondidas <span class="tabular-nums" x-text="answered"></span> de <span x-text="slides.length"></span></p>
            <p class="text-sm font-bold tabular-nums" :class="remaining <= 120 ? 'text-rose-700' : 'text-slate-700'" role="timer" aria-live="off">Tiempo: <span x-text="clock"></span></p>
        </div>
        <div class="mt-2 h-1.5 rounded-full bg-slate-100" aria-hidden="true"><div class="h-full rounded-full bg-blue-500 transition-[width] duration-300 ease-out" :style="'width:' + (Math.min(i + 1, slides.length) / slides.length * 100) + '%'"></div></div>
    </div>

    {{-- Mapa de preguntas: el color indica cuáles ya respondiste --}}
    <nav class="mt-4 flex flex-wrap gap-1.5" aria-label="Ir a una pregunta">
        <template x-for="(slide, index) in slides" :key="slide.id">
            <button type="button" @click="go(index)" class="grid h-9 w-9 place-items-center rounded-lg border text-sm font-bold tabular-nums transition"
                :class="index === i ? 'border-blue-600 bg-blue-600 text-white' : (chosen[slide.id] !== undefined ? 'border-blue-200 bg-blue-50 text-blue-800' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-400')"
                :aria-current="index === i ? 'step' : null" :aria-label="'Pregunta ' + (index + 1) + (chosen[slide.id] !== undefined ? ', respondida' : ', sin responder')" x-text="index + 1"></button>
        </template>
        <button type="button" @click="go(slides.length)" class="inline-flex h-9 items-center rounded-lg border px-3 text-sm font-bold transition"
            :class="i === slides.length ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-400'">Revisar y entregar</button>
    </nav>

    <div class="mt-4 min-h-[22rem] rounded-2xl border border-slate-200 bg-slate-50/60 p-5 sm:p-8">
        @foreach ($items as $index => $item)
            <fieldset x-show="i === {{ $index }}" @if ($index > 0) x-cloak @endif x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0">
                <legend class="sr-only">Pregunta {{ $index + 1 }}</legend>
                <p class="text-sm font-semibold text-blue-700">{{ $categories[$item['category']] ?? '' }}</p>
                @if (! empty($item['passage']))
                    <blockquote class="mt-3 max-w-2xl rounded-xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-700">{{ $item['passage'] }}</blockquote>
                @endif
                <h3 tabindex="-1" data-question-title class="mt-3 max-w-2xl text-balance text-xl font-extrabold leading-8 text-slate-900 outline-none">{{ $item['prompt'] }}</h3>
                <div class="mt-6 grid max-w-2xl gap-3">
                    @foreach ($item['options'] as $optionIndex => $option)
                        <label class="flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 leading-6 text-slate-700 transition hover:border-blue-400 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-slate-900 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-extrabold text-slate-600" aria-hidden="true">{{ chr(65 + $optionIndex) }}</span>
                            <input type="radio" name="choices[{{ $item['id'] }}]" value="{{ $optionIndex }}" class="sr-only" @change="pick(@js($item['id']), {{ $optionIndex }})">
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        {{-- Revisión final --}}
        <section x-show="i === slides.length" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-1 opacity-0" aria-labelledby="review-title">
            <h3 id="review-title" tabindex="-1" data-question-title class="text-2xl font-extrabold text-slate-900 outline-none">Revisa antes de entregar</h3>
            <p class="mt-2 max-w-2xl leading-7 text-slate-700" x-text="answered < slides.length ? 'Te faltan ' + (slides.length - answered) + ' preguntas. Las que dejes en blanco cuentan como incorrectas.' : 'Respondiste todas las preguntas. Puedes repasarlas o entregar.'"></p>
            <template x-if="answered < slides.length">
                <div class="mt-4 flex flex-wrap gap-2">
                    <template x-for="(slide, index) in slides" :key="slide.id">
                        <button type="button" x-show="chosen[slide.id] === undefined" @click="go(index)" class="inline-flex min-h-10 items-center rounded-xl border border-amber-300 bg-amber-50 px-3 text-sm font-bold text-amber-900 hover:border-amber-500" x-text="'Ir a la pregunta ' + (index + 1)"></button>
                    </template>
                </div>
            </template>
            <button class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50" :disabled="submitting" x-text="submitting ? 'Entregando…' : 'Entregar la prueba'"></button>
        </section>
    </div>

    <div class="mt-4 flex items-center justify-between gap-3">
        <button type="button" @click="go(i - 1)" :disabled="i === 0" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-400 disabled:cursor-not-allowed disabled:opacity-40">← Anterior</button>
        <p class="hidden text-center text-sm text-slate-500 sm:block">Puedes usar las flechas ← → del teclado</p>
        <button type="button" x-show="i < slides.length" @click="go(i + 1)" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700"
            x-text="i === slides.length - 1 ? 'Revisar y entregar →' : (chosen[slides[i]?.id] === undefined ? 'Saltar →' : 'Siguiente →')"></button>
    </div>
</form>

<script nonce="{{ Vite::cspNonce() }}">
function reasoningTest(config) {
    return {
        ...config, i: 0, chosen: {}, answered: 0, remaining: 0, clock: '', submitting: false, timer: null,
        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(this.key) || '{}');
                Object.entries(saved.choices || saved).forEach(([name, value]) => {
                    const input = this.$refs.form.querySelector(`input[name="${name}"][value="${value}"]`);
                    if (input) { input.checked = true; this.chosen[name.replace(/^choices\[(.*)\]$/, '$1')] = Number(value); }
                });
                if (Number.isInteger(saved.slide)) this.i = Math.min(Math.max(saved.slide, 0), this.slides.length);
            } catch (e) {}
            this.answered = Object.keys(this.chosen).length;
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        pick(id, value) {
            this.chosen = { ...this.chosen, [id]: value };
            this.answered = Object.keys(this.chosen).length;
            this.save();
        },
        go(index) {
            if (index < 0 || index > this.slides.length) return;
            this.i = index;
            this.save();
            // El foco va al enunciado para que el lector de pantalla lo lea y el teclado siga desde ahí.
            this.$nextTick(() => this.$el.querySelectorAll('[data-question-title]')[index]?.focus({ preventScroll: true }));
        },
        keys(event) {
            const tag = event.target.tagName;
            if (event.altKey || event.ctrlKey || event.metaKey || ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;
            if (event.key === 'ArrowRight') { event.preventDefault(); this.go(this.i + 1); }
            if (event.key === 'ArrowLeft') { event.preventDefault(); this.go(this.i - 1); }
        },
        save() {
            const choices = {};
            Object.entries(this.chosen).forEach(([id, value]) => { choices[`choices[${id}]`] = value; });
            try { localStorage.setItem(this.key, JSON.stringify({ choices, slide: this.i })); } catch (e) {}
        },
        clear() { try { localStorage.removeItem(this.key); } catch (e) {} },
        tick() {
            if (!this.deadline) { this.clock = 'sin límite'; return; }
            this.remaining = Math.max(0, Math.floor((new Date(this.deadline) - new Date()) / 1000));
            this.clock = `${String(Math.floor(this.remaining / 60)).padStart(2, '0')}:${String(this.remaining % 60).padStart(2, '0')}`;
            // Al terminar el tiempo se entrega sola, como en una prueba real.
            if (this.remaining === 0 && !this.submitting) {
                clearInterval(this.timer);
                this.submitting = true;
                this.clear();
                this.$refs.form.submit();
            }
        },
    };
}
</script>
