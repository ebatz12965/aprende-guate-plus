@extends('layouts.platform')
@section('content')
    @php($module = $topic->module)
    @php($lessonPending = in_array($topic->lesson_generation_status, ['queued', 'processing'], true))
    @php($practicePending = in_array($topic->practice_generation_status, ['queued', 'processing'], true))
    <div class="mx-auto max-w-5xl" @if ($lessonPending || $practicePending) x-data x-init="setTimeout(() => window.location.reload(), 4000)" @endif>
        <a href="{{ route('adaptive.path.item.show', $module) }}" class="text-sm font-bold text-blue-700">← Volver a {{ $module->title }}</a>
        <header class="topic-header mt-5 rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-6 sm:p-8">
            <h1 class="text-3xl font-black text-slate-900">{{ $topic->title }}</h1><p class="mt-1 text-sm font-semibold text-blue-600 topic-type-label">Tema {{ $topic->position }} · {{ str_replace('_', ' ', $topic->practice_type) }}</p>
            <p class="mt-3 max-w-3xl leading-7 text-slate-700">{{ $topic->learning_objective }}</p>
        </header>
        @if ($lessonPending || $practicePending)
            <div class="ag-animate-in mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-medium text-blue-900"><span
                    class="ag-spinner mr-2 inline-block align-[-.15em] text-blue-700"></span>Estamos preparando tu contenido. Esta página se actualizará
                automáticamente; puedes continuar navegando sin perder el proceso.</div>
        @endif
        <div class="mt-7 grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-wide text-blue-600">1. Lección</p>
                @if ($topic->lesson)
                    <h2 class="mt-2 text-xl font-black">Contenido para este tema</h2>
                    <p class="mt-3 leading-7 text-slate-700">{{ data_get($topic->lesson->content, 'summary') }}</p>
                    <h3 class="mt-5 font-black">Conceptos clave</h3>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                        @foreach (data_get($topic->lesson->content, 'key_concepts', []) as $concept)
                            <li>{{ $concept }}</li>
                        @endforeach
                    </ul>
                    <h3 class="mt-5 font-black">Proceso sugerido</h3>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-slate-700">
                        @foreach (data_get($topic->lesson->content, 'steps', []) as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach (data_get($topic->lesson->resources, 'youtube_queries', []) as $query)
                            <a target="_blank" rel="noopener" href="https://www.youtube.com/results?search_query={{ urlencode($query) }}"
                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700">Ver recurso en YouTube ↗</a>
                        @endforeach
                    </div>
                @elseif($lessonPending)
                    <p class="ag-soft-pulse mt-3 flex items-center gap-2 text-sm leading-6 text-blue-700"><span class="ag-spinner"></span>Generando explicación,
                    conceptos y recursos…</p>@else<p class="mt-3 text-sm leading-6 text-slate-600">Genera una explicación breve, conceptos, pasos y recursos
                        exclusivamente para este tema.</p>
                    @if ($topic->lesson_generation_status === 'failed')
                        <p class="mt-3 text-sm font-semibold text-rose-700">{{ $topic->generation_error }}</p>
                    @endif
                    <form method="POST" action="{{ route('adaptive.topic.lesson', $topic) }}" data-generation-form
                        data-generation-status-url="{{ route('adaptive.topic.generation.status', $topic) }}" data-generation-title="{{ $topic->title }}"
                        class="mt-5">
                        @csrf<button
                            class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">{{ $topic->lesson_generation_status === 'failed' ? 'Reintentar lección' : 'Generar lección' }}</button>
                    </form>
                @endif
            </section>
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-wide text-blue-600">2. Práctica</p>
                @if ($topic->practice)
                    <h2 class="mt-2 text-xl font-black">{{ $topic->practice->title }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-700">{{ data_get($topic->practice->instructions, 'context') }}</p>
                    <ol class="mt-4 list-decimal space-y-1 pl-5 text-sm text-slate-700">
                        @foreach (data_get($topic->practice->instructions, 'steps', []) as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                    @if ($topic->practice->type === 'excel_dataset')
                        <a href="{{ route('adaptive.topic.practice.download', $topic) }}"
                            class="mt-5 inline-block rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Descargar datos CSV</a>
                    @endif
                    <pre class="mt-5 max-h-80 overflow-auto rounded-xl bg-slate-950 p-4 text-xs leading-6 text-slate-100">
<code>{{ data_get($topic->practice->artifact, 'content') }}</code></pre>
                    <p class="mt-4 text-xs text-slate-500">{{ data_get($topic->practice->artifact, 'label') }} ·
                        {{ data_get($topic->practice->artifact, 'language') }}</p>
                    <form method="POST" action="{{ route('adaptive.topic.practice.submit', $topic) }}" class="mt-5">@csrf
                        <textarea required name="answer" rows="4" class="w-full rounded-xl border-slate-200"
                            placeholder="Describe tu solución, pega tu resultado o comparte el código corregido."></textarea><button class="mt-3 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Registrar práctica</button>
                    </form>@include('adaptive.partials.practice-feedback', ['attempt' => $latestAttempt ?? null])
                @elseif($practicePending)
                    <p class="ag-soft-pulse mt-3 flex items-center gap-2 text-sm leading-6 text-blue-700"><span class="ag-spinner"></span>Generando práctica y
                    material de apoyo…</p>@else<p class="mt-3 text-sm leading-6 text-slate-600">Se generará un artefacto adecuado para
                        {{ str_replace('_', ' ', $topic->practice_type) }}: datos, código o un caso profesional.</p>
                    @if ($topic->practice_generation_status === 'failed')
                        <p class="mt-3 text-sm font-semibold text-rose-700">{{ $topic->generation_error }}</p>
                    @endif
                    <form method="POST" action="{{ route('adaptive.topic.practice', $topic) }}" data-generation-form
                        data-generation-status-url="{{ route('adaptive.topic.generation.status', $topic) }}" data-generation-title="{{ $topic->title }}"
                        class="mt-5">
                        @csrf<button
                            class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">{{ $topic->practice_generation_status === 'failed' ? 'Reintentar práctica' : 'Generar práctica' }}</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
    <script>
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
