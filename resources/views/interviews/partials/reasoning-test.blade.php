{{-- Prueba de razonamiento en curso. Las respuestas correctas no se envían al navegador. --}}
@php
    $items = $stage->content['items'] ?? [];
    $sections = collect($items)->groupBy('category');
    $deadline = $stage->deadline();
@endphp
<form method="POST" action="{{ route('adaptive.interviews.stage.submit', [$simulation, $stage]) }}" class="mt-6 space-y-6"
    x-data="reasoningTest({ deadline: @js($deadline?->toIso8601String()), total: {{ count($items) }}, key: 'ag-reasoning-{{ $stage->id }}' })" x-ref="form" @submit="submitting = true; clear()">
    @csrf
    <div class="sticky top-[76px] z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 px-5 py-3 shadow-sm backdrop-blur">
        <p class="text-sm font-bold text-slate-700" aria-live="polite">Respondidas <span x-text="answered"></span> de {{ count($items) }}</p>
        <p class="text-sm font-bold tabular-nums" :class="remaining <= 120 ? 'text-rose-700' : 'text-slate-700'" role="timer" aria-live="off">
            Tiempo: <span x-text="clock"></span>
        </p>
    </div>

    @foreach (\App\Services\Interviews\ReasoningTestService::CATEGORIES as $category => $label)
        @if ($sections->has($category))
            <section class="learning-panel" aria-labelledby="sec-{{ $category }}">
                <h2 id="sec-{{ $category }}" class="text-lg font-extrabold text-slate-900">{{ $label }}</h2>
                <div class="mt-4 space-y-6">
                    @foreach ($sections[$category] as $item)
                        <fieldset class="border-t border-slate-100 pt-5 first:border-t-0 first:pt-0">
                            <legend class="sr-only">Pregunta {{ substr($item['id'], 1) }}</legend>
                            @if (! empty($item['passage']))
                                <blockquote class="mb-3 rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $item['passage'] }}</blockquote>
                            @endif
                            <p class="font-semibold leading-7 text-slate-900"><span class="text-slate-500">{{ substr($item['id'], 1) }}.</span> {{ $item['prompt'] }}</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($item['options'] as $index => $option)
                                    <label class="flex min-h-11 cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700 transition has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50 hover:border-blue-300">
                                        <input type="radio" name="choices[{{ $item['id'] }}]" value="{{ $index }}" class="mt-0.5 h-4 w-4 border-slate-300 text-blue-700 focus:ring-blue-500" @change="save()">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-600" x-text="answered < total ? `Te faltan ${total - answered} preguntas. Las que dejes en blanco cuentan como incorrectas.` : 'Respondiste todas las preguntas.'"></p>
        <button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50" :disabled="submitting" x-text="submitting ? 'Entregando…' : 'Entregar la prueba'"></button>
    </div>
</form>

<script>
function reasoningTest(config) {
    return {
        ...config, answered: 0, remaining: 0, clock: '', submitting: false, timer: null,
        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(this.key) || '{}');
                Object.entries(saved).forEach(([name, value]) => {
                    const input = this.$refs.form.querySelector(`input[name="${name}"][value="${value}"]`);
                    if (input) input.checked = true;
                });
            } catch (e) {}
            this.count();
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        count() { this.answered = this.$refs.form.querySelectorAll('input[type=radio]:checked').length; },
        save() {
            this.count();
            const values = {};
            this.$refs.form.querySelectorAll('input[type=radio]:checked').forEach((input) => { values[input.name] = input.value; });
            try { localStorage.setItem(this.key, JSON.stringify(values)); } catch (e) {}
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
