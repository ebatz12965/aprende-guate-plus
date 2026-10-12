@extends('layouts.platform')
@section('content')
    @php($module = $topic->module)
    @php($lessonPending = in_array($topic->lesson_generation_status, ['queued', 'processing'], true))
    @php($practicePending = in_array($topic->practice_generation_status, ['queued', 'processing'], true))
    <div class="mx-auto max-w-5xl pb-20" @if ($lessonPending || $practicePending) x-data x-init="setTimeout(() => window.location.reload(), 4000)" @endif>
        <a href="{{ route('adaptive.path.item.show', $module) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-blue-700">← Volver a {{ $module->title }}</a>
        <header class="topic-header mt-3 rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-6 sm:p-8">
            <h1 class="text-balance text-3xl font-black text-slate-900">{{ $topic->title }}</h1>
            <p class="topic-type-label mt-1 text-sm font-semibold text-blue-600">Tema {{ $topic->position }} de la fase {{ $module->position }} · {{ $module->title }}</p>
            <p class="mt-3 max-w-3xl leading-7 text-slate-700">{{ $topic->learning_objective }}</p>
        </header>

        @if ($lessonPending || $practicePending)
            <div class="ag-animate-in mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-medium text-blue-900 dark:text-blue-50" role="status"><span
                    class="ag-spinner mr-2 inline-block align-[-.15em] text-blue-700"></span>Estamos preparando tu contenido y revisando su calidad; puede tardar uno o dos minutos.
                Esta página se actualizará sola y puedes seguir navegando.</div>
        @endif

        @php($lastAttempt = $attempts->first())
        @php($practiceBadge = $lastAttempt ? ($lastAttempt->status === 'evaluated' ? $lastAttempt->score.'/100' : (in_array($lastAttempt->status, ['queued', 'evaluating'], true) ? 'en revisión' : 'enviada')) : ($practicePending ? 'preparando' : null))
        @php($lessonBadge = $lessonPending ? 'preparando' : null)
        {{-- Lección y práctica en pestañas: cada una tiene su propio espacio. La pestaña activa vive en la dirección (#leccion o #practica). --}}
        <div x-data="{ tab: location.hash === '#practica' ? 'practica' : 'leccion',
                show(name, focus = false) { this.tab = name; history.replaceState(null, '', '#' + name); if (focus) this.$nextTick(() => this.$refs['tab-' + name].focus()); },
                fromHash() { if (['#leccion', '#practica'].includes(location.hash)) { this.tab = location.hash.slice(1); this.$nextTick(() => this.$refs.tabs.scrollIntoView({ behavior: 'smooth', block: 'start' })); } } }"
            @hashchange.window="fromHash()" class="mt-7">
            <div x-ref="tabs" role="tablist" aria-label="Partes del tema" class="flex scroll-mt-24 gap-2 border-b border-slate-200"
                @keydown.right.prevent="show(tab === 'leccion' ? 'practica' : 'leccion', true)" @keydown.left.prevent="show(tab === 'leccion' ? 'practica' : 'leccion', true)">
                @foreach (['leccion' => ['Lección', $lessonBadge], 'practica' => ['Práctica', $practiceBadge]] as $name => [$label, $badge])
                    <button type="button" role="tab" id="tab-{{ $name }}" x-ref="tab-{{ $name }}" aria-controls="panel-{{ $name }}" @click="show('{{ $name }}')"
                        :aria-selected="tab === '{{ $name }}'" :tabindex="tab === '{{ $name }}' ? 0 : -1"
                        class="-mb-px inline-flex min-h-12 items-center gap-2 border-b-2 px-4 text-base font-extrabold transition"
                        :class="tab === '{{ $name }}' ? 'border-blue-600 text-slate-900' : 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900'">
                        {{ $label }}
                        @if ($badge)<span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-bold tabular-nums text-blue-700">{{ $badge }}</span>@endif
                    </button>
                @endforeach
            </div>

        <section id="panel-leccion" role="tabpanel" aria-labelledby="tab-leccion" x-show="tab === 'leccion'" class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="sr-only">Lección</h2>
            @if ($topic->lesson)
                <div class="mt-5">@include('adaptive.partials.lesson', ['lesson' => $topic->lesson, 'practiceType' => $topic->practice_type, 'topicTitle' => $topic->title, 'topic' => $topic])</div>
            @elseif($lessonPending)
                <p class="ag-soft-pulse mt-3 flex items-center gap-2 text-sm leading-6 text-blue-700"><span class="ag-spinner"></span>Escribiendo la lección y revisando que todo sea correcto…</p>
            @else
                <p class="max-w-2xl leading-7 text-slate-600">Una explicación completa de este tema: conceptos con ejemplos, un ejemplo resuelto paso a paso, errores frecuentes y preguntas para comprobar lo que aprendiste.</p>
                @if ($topic->lesson_generation_status === 'failed')
                    <p class="mt-3 text-sm font-semibold text-rose-700">{{ $topic->generation_error }}</p>
                @endif
                <form method="POST" action="{{ route('adaptive.topic.lesson', $topic) }}" data-generation-form
                    data-generation-status-url="{{ route('adaptive.topic.generation.status', $topic) }}" data-generation-title="{{ $topic->title }}" class="mt-5">
                    @csrf<button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">{{ $topic->lesson_generation_status === 'failed' ? 'Intentar de nuevo' : 'Preparar la lección' }}</button>
                </form>
            @endif
        </section>

        <section id="panel-practica" role="tabpanel" aria-labelledby="tab-practica" x-show="tab === 'practica'" x-cloak class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="sr-only">Práctica</h2>
            @if ($topic->practice)
                <div class="mt-5">@include('adaptive.partials.practice', ['practice' => $topic->practice, 'attempts' => $attempts])</div>
            @elseif($practicePending)
                <p class="ag-soft-pulse mt-3 flex items-center gap-2 text-sm leading-6 text-blue-700"><span class="ag-spinner"></span>Preparando la práctica, sus datos y sus criterios de evaluación…</p>
            @else
                <p class="max-w-2xl leading-7 text-slate-600">Una tarea como las de un trabajo real para aplicar el tema, con {{ match ($topic->practice_type) { 'excel_dataset' => 'datos para trabajar en Excel', 'code_bug', 'code_complete' => 'código para corregir o completar', default => 'un caso para resolver' } }}, pistas si te atascas y retroalimentación de cada intento.</p>
                @if (! $topic->lesson)
                    <p class="mt-2 text-sm text-slate-600">Te recomendamos <button type="button" @click="show('leccion')" class="font-bold text-blue-700 underline underline-offset-4">ver la lección</button> primero.</p>
                @endif
                @if ($topic->practice_generation_status === 'failed')
                    <p class="mt-3 text-sm font-semibold text-rose-700">{{ $topic->generation_error }}</p>
                @endif
                <form method="POST" action="{{ route('adaptive.topic.practice', $topic) }}" data-generation-form
                    data-generation-status-url="{{ route('adaptive.topic.generation.status', $topic) }}" data-generation-title="{{ $topic->title }}" class="mt-5">
                    @csrf<button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">{{ $topic->practice_generation_status === 'failed' ? 'Intentar de nuevo' : 'Preparar la práctica' }}</button>
                </form>
            @endif
        </section>
        </div>
    </div>
    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', () => {
            const key = 'ag-topic-generation-{{ $topic->id }}';
            @if ($lessonPending || $practicePending)
                localStorage.setItem(key, 'pending');
            @elseif ($topic->lesson || $topic->practice)
                if (localStorage.getItem(key) === 'pending') {
                    localStorage.removeItem(key);
                    window.agToast?.('El contenido del tema ya está listo', {
                        type: 'success'
                    });
                }
            @endif
        });
    </script>
@endsection
