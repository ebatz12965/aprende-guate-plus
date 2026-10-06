@extends('layouts.platform')

@section('content')
@php
    $initialsOf = fn (?string $name) => collect(explode(' ', (string) $name))->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('');
    $initials = $initialsOf($stage->interviewer['name'] ?? '');
    $evaluationStatus = $stage->content['evaluation_status'] ?? null;
    $tips = match ($stage->type) {
        'hr_interview' => ['Responde con ejemplos concretos: la situación, lo que tenías que hacer, lo que hiciste y el resultado (método STAR).', 'Lee la descripción de la empresa ficticia; te preguntarán por qué quieres trabajar ahí.', 'Prepara una o dos preguntas para hacerle a la empresa al final.'],
        'reasoning_test' => ['Lee cada pregunta completa antes de responder.', 'Si una pregunta te traba, sigue con la próxima y vuelve después.', 'En las situaciones de trabajo no hay trampa: elige lo que de verdad harías.'],
        'technical_interview' => ['Explica cómo llegas a tu respuesta, no solo la respuesta.', 'Si no sabes algo, dilo y explica cómo lo averiguarías: eso también cuenta.', 'Usa ejemplos de tus prácticas o de trabajos anteriores.'],
        default => ['Lee el caso y el entregable completos antes de empezar.', 'Organiza tu respuesta en pasos y justifica tus decisiones.', 'Revisa tu entrega antes de enviarla: cuenta como en una prueba real.'],
    };
    $poll = fn (string $until) => "{ async check() { try { const r = await fetch('".route('adaptive.interviews.stage.status', [$simulation, $stage])."', { headers: { Accept: 'application/json', 'X-Background-Request': '1' } }); if (r.ok) { const s = await r.json(); if ({$until}) { window.location.reload(); return; } } } catch (e) {} setTimeout(() => this.check(), 3000); } }";
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
                    @if (! empty($stage->interviewer['style']) && in_array($stage->status, ['available', 'preparing'], true))<p class="mt-2 text-sm leading-6 text-slate-600">{{ $stage->interviewer['style'] }}</p>@endif
                </div>
            </div>
        @endif

        @if (in_array($stage->status, ['available', 'skipped'], true))
            @if (! empty($stage->content['error']))<p class="mt-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $stage->content['error'] }}</p>@endif
            <p class="mt-5 leading-7 text-slate-700">{{ $stage->purpose }}</p>
            @if ($stage->focus)
                <h2 class="mt-6 font-extrabold text-slate-900">{{ $stage->type === 'practical_test' ? 'Qué entregarás' : 'Qué se evalúa' }}</h2>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($stage->focus as $topic)
                        <li class="flex gap-2 text-slate-700"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-500" aria-hidden="true"></span>{{ $topic }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($stage->status === 'available')
                <h2 class="mt-6 font-extrabold text-slate-900">Consejos</h2>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($tips as $tip)
                        <li class="flex gap-2 text-slate-700"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>{{ $tip }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-8 border-t border-slate-100 pt-6">
                @if ($stage->status === 'skipped')
                    <p class="text-slate-600">Saltaste esta etapa en una versión anterior del simulador.</p>
                @elseif ($stage->type === 'hr_interview')
                    <h2 class="font-extrabold text-slate-900">¿Cómo quieres hacerla?</h2>
                    <form method="POST" action="{{ route('adaptive.interviews.stage.start', [$simulation, $stage]) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <button name="mode" value="chat" class="rounded-2xl border-2 border-slate-200 p-4 text-left hover:border-blue-500 hover:bg-blue-50 focus-visible:border-blue-600">
                            <span class="block font-extrabold text-slate-900">Por chat</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600">Respondes por escrito, una pregunta a la vez y a tu ritmo.</span>
                        </button>
                        <button name="mode" value="voice" @disabled($voiceUnavailable) class="rounded-2xl border-2 border-slate-200 p-4 text-left hover:border-blue-500 hover:bg-blue-50 focus-visible:border-blue-600 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:border-slate-200 disabled:hover:bg-transparent">
                            <span class="block font-extrabold text-slate-900">Por voz <span class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800">corta</span></span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600">Una llamada de hasta {{ $voiceMaxMinutes }} minutos y {{ config('ai.voice.questions') }} preguntas. Necesitas micrófono y audífonos o bocinas.</span>
                        </button>
                    </form>
                    @if ($voiceUnavailable)<p class="mt-3 text-sm font-medium text-slate-600">{{ $voiceUnavailable }}</p>@endif
                @else
                    <form method="POST" action="{{ route('adaptive.interviews.stage.start', [$simulation, $stage]) }}" class="flex flex-wrap items-center gap-3">
                        @csrf
                        <button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">{{ $stage->isTest() ? 'Comenzar la prueba' : 'Comenzar la entrevista' }}</button>
                        <p class="text-sm text-slate-500">{{ match ($stage->type) {
                            'reasoning_test' => '12 preguntas de opción múltiple. El tiempo empieza a contar cuando se abre la prueba.',
                            'practical_test' => 'Recibirás el caso y sus materiales. Respondes por escrito o adjuntas un archivo.',
                            default => 'Responderás por escrito, una pregunta a la vez.',
                        } }}</p>
                    </form>
                @endif
            </div>
        @endif
    </section>

    @if ($stage->status === 'preparing')
        <section class="mt-6 rounded-3xl border border-blue-100 bg-white p-8 text-center shadow-sm" x-data="{{ $poll("s.status !== 'preparing'") }}" x-init="setTimeout(() => check(), 3000)">
            <span class="ag-soft-pulse ag-brand-loading mx-auto grid h-14 w-14 place-items-center rounded-2xl p-2"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt=""></span>
            <h2 class="mt-6 text-2xl font-black text-slate-900">{{ $stage->isTest() ? 'Estamos preparando tu prueba' : ($stage->interviewer['name'] ?? 'La entrevista').' está preparando sus preguntas' }}</h2>
            <p class="ag-soft-pulse mt-4 text-sm font-semibold text-blue-700" aria-live="polite">Esta página se actualiza sola.</p>
        </section>
    @endif

    @if ($stage->status === 'in_progress' && $stage->type === 'reasoning_test')
        @include('interviews.partials.reasoning-test')
    @elseif ($stage->status === 'in_progress' && $stage->type === 'practical_test')
        @include('interviews.partials.practical-test')
    @elseif ($stage->status === 'in_progress' && ($stage->content['mode'] ?? null) === 'voice')
        @include('interviews.partials.voice-call')
    @elseif ($stage->status === 'in_progress')
        @php $content = $stage->content; @endphp
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            x-data="interviewChat({ turns: @js($turns), url: @js(route('adaptive.interviews.stage.answer', [$simulation, $stage])), question: {{ ($content['current'] ?? 0) + 1 }}, total: {{ count($content['questions'] ?? []) }}, draftKey: 'ag-interview-{{ $stage->id }}', max: {{ \App\Services\Interviews\InterviewConversation::MAX_ANSWER }}, initials: @js($initials) })"
           >
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <p class="text-sm font-bold text-slate-700" aria-live="polite">Pregunta <span x-text="question"></span> de <span x-text="total"></span></p>
                <div class="h-2 w-32 rounded-full bg-slate-100" aria-hidden="true"><div class="h-full rounded-full bg-blue-600 transition-all" :style="`width: ${Math.min(100, (question - 1) / total * 100)}%`"></div></div>
            </div>
            <div x-ref="log" class="max-h-[60vh] min-h-[18rem] space-y-4 overflow-y-auto p-5" role="log" aria-live="polite" aria-label="Conversación de la entrevista">
                <template x-for="(turn, index) in turns" :key="index">
                    <div class="flex gap-3" :class="turn.role === 'candidate' ? 'justify-end' : ''">
                        <span x-show="turn.role !== 'candidate'" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-blue-100 text-xs font-extrabold text-blue-800" aria-hidden="true" x-text="initials"></span>
                        <div class="max-w-[85%] rounded-2xl p-4 text-sm leading-6"
                            :class="turn.role === 'candidate' ? 'rounded-tr-sm bg-blue-700 text-white' : 'rounded-tl-sm bg-slate-100 text-slate-800'">
                            <p x-show="turn.kind === 'follow_up'" class="mb-1 text-xs font-bold text-blue-800">Repregunta</p>
                            <span class="whitespace-pre-line" x-text="turn.content"></span>
                        </div>
                    </div>
                </template>
                <div x-show="sending" x-cloak class="flex gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-blue-100 text-xs font-extrabold text-blue-800" aria-hidden="true" x-text="initials"></span>
                    <div class="ag-soft-pulse rounded-2xl rounded-tl-sm bg-slate-100 px-4 py-3 text-sm text-slate-600">Escribiendo…</div>
                </div>
            </div>
            <form @submit.prevent="send()" class="border-t border-slate-100 p-4" x-show="! finished">
                <label for="answer" class="sr-only">Tu respuesta</label>
                <textarea id="answer" x-ref="input" x-model="answer" @input="saveDraft()" @keydown.ctrl.enter.prevent="send()" @keydown.meta.enter.prevent="send()"
                    :maxlength="max" rows="4" placeholder="Escribe tu respuesta…" :disabled="sending"
                    class="block w-full resize-y rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500"><span x-text="answer.length"></span>/<span x-text="max"></span> · Ctrl + Enter para enviar · tu borrador se guarda en este navegador</p>
                    <button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50" :disabled="sending || answer.trim().length < 2" x-text="sending ? 'Enviando…' : 'Enviar respuesta'"></button>
                </div>
                <p x-show="error" x-cloak class="mt-2 text-sm font-medium text-rose-700" x-text="error" role="alert"></p>
            </form>
            <p x-show="finished" x-cloak class="border-t border-slate-100 p-4 text-sm font-semibold text-emerald-800">Entrevista terminada. Preparando tu retroalimentación…</p>
        </section>
    @endif

    @if ($stage->status === 'evaluated' && $stage->type === 'reasoning_test')
        @include('interviews.partials.reasoning-result')
    @elseif (in_array($stage->status, ['submitted', 'evaluated'], true))
        @php
            $evaluation = $stage->evaluation;
            $feedback = collect($evaluation['per_question'] ?? [])->keyBy('question');
            $number = 0;
        @endphp
        @if ($stage->status === 'submitted' && $evaluationStatus !== 'failed')
            <section class="mt-6 rounded-3xl border border-blue-100 bg-white p-8 text-center shadow-sm" x-data="{{ $poll("s.status === 'evaluated' || s.evaluation === 'failed'") }}" x-init="setTimeout(() => check(), 3000)">
                <h2 class="text-2xl font-black text-slate-900">Estamos evaluando tu {{ $stage->isTest() ? 'prueba' : 'entrevista' }}</h2>
                <p class="mt-2 text-slate-600">Ya puedes seguir con la siguiente etapa; la retroalimentación aparecerá aquí.</p>
                <p class="ag-soft-pulse mt-4 text-sm font-semibold text-blue-700" aria-live="polite">Esta página se actualiza sola.</p>
            </section>
        @elseif ($stage->status === 'submitted')
            <section class="mt-6 rounded-2xl border border-amber-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-extrabold text-slate-900">Tu {{ $stage->isTest() ? 'entrega' : 'entrevista' }} quedó guardada</h2>
                <p class="mt-2 text-slate-600">El evaluador no estaba disponible. Puedes pedir la retroalimentación otra vez.</p>
                <form method="POST" action="{{ route('adaptive.interviews.stage.retry', [$simulation, $stage]) }}" class="mt-4">@csrf<button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">Pedir la retroalimentación</button></form>
            </section>
        @else
            <section class="mt-6 learning-panel" aria-labelledby="resultado">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="grid h-24 w-24 shrink-0 place-items-center rounded-2xl {{ $stage->score >= 70 ? 'bg-emerald-50 text-emerald-800' : ($stage->score >= 50 ? 'bg-amber-50 text-amber-800' : 'bg-rose-50 text-rose-800') }}">
                        <p class="text-center"><span class="block text-3xl font-black tabular-nums">{{ $stage->score }}</span><span class="text-xs font-bold">de 100</span></p>
                    </div>
                    <div>
                        <h2 id="resultado" class="text-xl font-extrabold text-slate-900">Tu retroalimentación</h2>
                        <p class="mt-1 leading-7 text-slate-700">{{ $evaluation['summary'] }}</p>
                    </div>
                </div>
                <dl class="mt-6 space-y-4">
                    @foreach ($evaluation['criteria'] as $criterion)
                        <div>
                            <div class="flex items-baseline justify-between gap-3 text-sm"><dt class="font-bold text-slate-900">{{ $criterion['name'] }}@isset($criterion['weight'])<span class="font-normal text-slate-500"> · {{ $criterion['weight'] }} %</span>@endisset</dt><dd class="shrink-0 font-bold tabular-nums text-slate-700">{{ $criterion['score'] }}</dd></div>
                            <div class="mt-1.5 h-2 rounded-full bg-slate-100" role="progressbar" aria-label="{{ $criterion['name'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $criterion['score'] }}"><div class="h-full rounded-full bg-blue-600" style="width: {{ $criterion['score'] }}%"></div></div>
                            <p class="mt-1.5 text-sm leading-6 text-slate-600">{{ $criterion['feedback'] }}</p>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-emerald-50 p-4"><h3 class="font-extrabold text-emerald-900">Lo que hiciste bien</h3><ul class="mt-2 space-y-1.5 text-sm leading-6 text-emerald-950">@foreach ($evaluation['strengths'] as $item)<li>{{ $item }}</li>@endforeach</ul></div>
                    <div class="rounded-2xl bg-amber-50 p-4"><h3 class="font-extrabold text-amber-900">Para la próxima</h3><ul class="mt-2 space-y-1.5 text-sm leading-6 text-amber-950">@foreach ($evaluation['improvements'] as $item)<li>{{ $item }}</li>@endforeach</ul></div>
                </div>
            </section>
        @endif

        @if ($stage->type === 'practical_test')
            @include('interviews.partials.practical-submission')
        @else
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="transcripcion">
            <h2 id="transcripcion" class="text-lg font-extrabold text-slate-900">Tu entrevista</h2>
            <ol class="mt-4 space-y-4">
                @foreach ($stage->turns as $turn)
                    @php $kind = $turn->meta['kind'] ?? ''; @endphp
                    @if ($kind === 'question') @php $number++; @endphp @endif
                    <li class="{{ $turn->role === 'candidate' ? 'ml-6 border-l-2 border-blue-200 pl-4' : '' }}">
                        <p class="text-xs font-bold text-slate-500">{{ match ($kind) { 'question' => 'Pregunta '.$number, 'follow_up' => 'Repregunta', 'answer' => 'Tu respuesta', 'opening' => 'Inicio', default => 'Cierre' } }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 {{ $turn->role === 'candidate' ? 'text-slate-800' : 'text-slate-600' }}">{{ $turn->content }}</p>
                    </li>
                    {{-- La retroalimentación de cada pregunta va después de su última respuesta. --}}
                    @php $nextKind = $stage->turns[$loop->index + 1]->meta['kind'] ?? null; @endphp
                    @if ($turn->role === 'candidate' && $nextKind !== 'answer' && $nextKind !== 'follow_up' && ($tip = $feedback[$number] ?? null))
                        <li class="ml-6 rounded-xl bg-blue-50 p-3 text-sm leading-6 text-blue-950">
                            <p class="font-bold">Sobre la pregunta {{ $number }}</p>
                            <p class="mt-1">{{ $tip['feedback'] }}</p>
                            @if ($tip['tip'])<p class="mt-1"><span class="font-semibold">Cómo mejorarla:</span> {{ $tip['tip'] }}</p>@endif
                        </li>
                    @endif
                @endforeach
            </ol>
        </section>

        @endif

        @if ($next)
            <div class="mt-6 flex justify-end">
                <a href="{{ route('adaptive.interviews.stage', [$simulation, $next]) }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">Siguiente etapa: {{ $next->title }} →</a>
            </div>
        @endif
    @endif
</div>

<script>
function interviewChat(config) {
    return {
        ...config, answer: '', sending: false, error: '', finished: false,
        init() {
            try { this.answer = localStorage.getItem(this.draftKey) || ''; } catch (e) {}
            this.$nextTick(() => { this.scroll(); this.$refs.input?.focus(); });
        },
        saveDraft() { try { localStorage.setItem(this.draftKey, this.answer); } catch (e) {} },
        clearDraft() { try { localStorage.removeItem(this.draftKey); } catch (e) {} },
        scroll() { this.$nextTick(() => { this.$refs.log.scrollTop = this.$refs.log.scrollHeight; }); },
        async send() {
            const text = this.answer.trim();
            if (this.sending || text.length < 2) return;
            this.sending = true; this.error = '';
            this.turns.push({ role: 'candidate', content: text, kind: 'answer' });
            this.scroll();
            try {
                const response = await fetch(this.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                    body: JSON.stringify({ answer: text }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    this.turns.pop();
                    this.error = response.status === 429 ? 'Vas muy rápido. Espera unos segundos y vuelve a enviar.' : (data.message || 'No se pudo enviar tu respuesta. Inténtalo de nuevo.');
                    return;
                }
                this.turns.push(...data.turns.filter((turn) => turn.role !== 'candidate'));
                this.question = data.question; this.total = data.total;
                this.answer = ''; this.clearDraft();
                if (data.status !== 'in_progress') {
                    this.finished = true;
                    setTimeout(() => window.location.reload(), 1800);
                }
            } catch (e) {
                this.turns.pop();
                this.error = 'Se perdió la conexión. Tu respuesta sigue en el cuadro; vuelve a enviarla.';
            } finally {
                this.sending = false; this.scroll();
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },
    };
}
</script>
@endsection
