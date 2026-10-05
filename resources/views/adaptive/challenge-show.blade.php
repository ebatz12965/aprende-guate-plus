@extends('layouts.platform')
@section('content')
    @php($evaluation = $submission?->evaluation ?? [])
    <div class="mx-auto max-w-6xl">
        <a href="{{ route('adaptive.challenges') }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">← Volver al laboratorio</a>
        <section class="mt-5 overflow-hidden rounded-3xl bg-slate-950 p-7 text-white shadow-xl sm:p-9">
            <p class="text-xs font-extrabold tracking-[.18em] text-blue-300">PRUEBA · {{ Str::upper($challenge->area ?? 'COMPETENCIA') }}</p>
            <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold">{{ $challenge->title }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">{{ $challenge->description }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm"><b>{{ $challenge->estimated_minutes ?? '—' }}</b> min estimados</div>
            </div>
        </section>
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <main class="space-y-6 lg:col-span-2">
                <section class="learning-panel">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-blue-600">Contexto del caso</p>
                    <p class="mt-3 whitespace-pre-line leading-7 text-slate-700">{{ $challenge->case_context ?: $challenge->description }}</p>
                </section>
                <section class="learning-panel">
                    <h2 class="text-xl font-extrabold text-slate-900">Qué debes lograr</h2>
                    <ul class="mt-4 space-y-3">
                        @forelse($challenge->objectives ?? [] as $objective)
                            <li class="flex gap-3 text-sm leading-6 text-slate-700"><span
                                    class="mt-1 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-100 text-xs font-black text-blue-700">✓</span>{{ $objective }}
                        </li>@empty<li class="text-sm text-slate-600">{{ $challenge->instructions }}</li>
                        @endforelse
                    </ul>
                </section>
                <section class="learning-panel">
                    <h2 class="text-xl font-extrabold text-slate-900">Entregables requeridos</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($challenge->deliverables ?? [] as $deliverable)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <p class="font-bold text-slate-800">{{ data_get($deliverable, 'name', $deliverable) }}</p>
                                @if (data_get($deliverable, 'detail'))
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ data_get($deliverable, 'detail') }}</p>
                                @endif
                            </div>
                        @empty<p class="text-sm text-slate-600">Explica tu resultado y el procedimiento que seguiste.</p>
                        @endforelse
                    </div>
                </section>
                <section class="learning-panel">
                    <h2 class="text-xl font-extrabold text-slate-900">Requisitos y criterios de aceptación</h2>
                    <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm leading-6 text-slate-700">
                        @foreach ($challenge->requirements ?? [$challenge->instructions] as $requirement)
                            <li>{{ $requirement }}</li>
                        @endforeach
                    </ol>
                </section>
            </main>
            <aside class="space-y-6">
                <section class="learning-panel">
                    <h2 class="font-extrabold text-slate-900">Cómo se evaluará</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($challenge->rubric ?? [] as $criterion)
                            <div>
                                <div class="flex justify-between gap-3 text-sm"><span
                                        class="font-bold text-slate-800">{{ data_get($criterion, 'name') }}</span><span
                                        class="text-slate-500">{{ data_get($criterion, 'weight') }}%</span></div>
                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ data_get($criterion, 'description') }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
                <section class="learning-panel">
                    <h2 class="font-extrabold text-slate-900">Material de apoyo</h2>
                    <div class="mt-4 space-y-2">
                        @forelse($challenge->resources ?? [] as $index => $resource)
                            <a href="{{ route('adaptive.challenges.resource', [$challenge, $index]) }}"
                                class="flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50 px-3 py-3 text-sm font-bold text-blue-700">{{ data_get($resource, 'label', 'Descargar material') }}
                            <span>↓</span></a>@empty<p class="text-sm leading-6 text-slate-600">No necesitas descargar archivos para esta prueba.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
        <section class="mt-6 learning-panel">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Entrega y evaluación</h2>
                </div>
                @if ($submission)
                    <span
                        class="rounded-full px-3 py-1.5 text-sm font-bold {{ $submission->status === 'evaluated' ? 'bg-emerald-50 text-emerald-700' : ($submission->status === 'evaluation_failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ match ($submission->status) {'queued' => 'Lista para evaluar','evaluating' => 'Evaluando evidencia','evaluated' => 'Evaluación lista','requires_ai' => 'Pendiente de IA','evaluation_failed' => 'Evaluación pendiente',default => 'Evidencia guardada'} }}</span>
                @endif
            </div>
            @if ($submission && $submission->status === 'evaluated')
                <div class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-emerald-800">Resultado de evaluación</p>
                            <p class="mt-1 text-sm leading-6 text-emerald-900">{{ $submission->feedback }}</p>
                        </div>
                        <p class="text-4xl font-black text-emerald-700">{{ $submission->score }}<span class="text-lg">/100</span></p>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @foreach (data_get($evaluation, 'criteria', []) as $criterion)
                            <div class="rounded-xl bg-white/80 p-4">
                                <p class="font-bold text-slate-800">{{ data_get($criterion, 'name') }}</p>
                                <p class="mt-1 text-sm font-bold text-blue-700">{{ data_get($criterion, 'score') }}/{{ data_get($criterion, 'max_score') }}</p>
                                <p class="mt-2 text-xs leading-5 text-slate-600">{{ data_get($criterion, 'feedback') }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="font-bold text-slate-800">Fortalezas</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                @foreach (data_get($evaluation, 'strengths', []) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="font-bold text-slate-800">Siguiente mejora</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                @foreach (data_get($evaluation, 'improvements', []) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <p class="mt-5 text-xs leading-5 text-slate-500">{{ data_get($evaluation, 'integrity_note') }}</p>
                </div>
            @elseif($submission)
                <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                    {{ $submission->feedback ?? 'La evidencia fue recibida. Mantén el worker de la cola IA activo para obtener la evaluación.' }}@if ($submission->attachment_path)
                        <br><a href="{{ route('adaptive.challenges.attachment', $submission) }}" class="mt-2 inline-block font-bold underline">Descargar mi archivo
                            adjunto: {{ $submission->attachment_name }}</a>
                    @endif
                </div>
            @endif
            <form method="POST" action="{{ route('adaptive.challenges.submit', $challenge) }}" enctype="multipart/form-data" class="mt-6"
                x-data="{ dragging: false, name: '{{ addslashes($submission?->attachment_name ?? '') }}', choose(event) { this.name = event.target.files[0]?.name || '' }, drop(event) { this.dragging = false; if (event.dataTransfer.files.length) { this.$refs.attachment.files = event.dataTransfer.files;
                            this.name = event.dataTransfer.files[0].name; } } }">@csrf<div class="grid gap-5 lg:grid-cols-2"><label class="block"><span class="text-sm font-bold text-slate-800">Resultado o
                            respuesta</span>
                        <textarea name="answer" rows="8" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Explica tu respuesta, tus hallazgos, cálculos, decisiones o pega el código solicitado.">{{ old('answer', $submission?->answer) }}</textarea><span class="mt-1 block text-xs text-slate-500">Puedes entregar texto, archivo o ambos; pero describe tu método
                            abajo.</span>
                    </label>
                    <div><label class="block"><span class="text-sm font-bold text-slate-800">Método utilizado <span class="text-rose-600">*</span></span>
                            <textarea required name="methodology" rows="4" class="mt-2 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Describe paso a paso cómo resolviste el ejercicio, qué herramientas usaste y cómo validaste tu resultado.">{{ old('methodology', $submission?->methodology) }}</textarea>
                        </label>
                        <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
                            :class="dragging ? 'border-blue-500 bg-blue-50' : 'border-dashed border-slate-300 bg-slate-50'"
                            class="mt-4 rounded-xl border-2 p-5 text-center transition"><input x-ref="attachment" @change="choose($event)" type="file"
                                name="attachment" class="sr-only" id="challenge-attachment"
                                accept=".pdf,.txt,.csv,.xlsx,.xls,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg,.webp,.zip"><label for="challenge-attachment"
                                class="cursor-pointer"><span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-white text-xl shadow-sm">↑</span>
                                <p class="mt-2 text-sm font-bold text-slate-800">Arrastra un archivo aquí o examina tu equipo</p>
                                <p class="mt-1 text-xs text-slate-500" x-text="name || 'PDF, CSV, Excel, Word, PowerPoint, imagen, texto o ZIP · máximo 20 MB'">
                                </p>
                                <p class="mt-1 text-xs text-slate-500">El evaluador IA lee el texto de PDF, Word (.docx), Excel (.xlsx), PowerPoint (.pptx), TXT y
                                    CSV. Las imágenes y los ZIP se guardan, pero se evalúan con tu respuesta escrita.</p>
                            </label></div>
                    </div>
                </div><button
                    class="mt-5 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">{{ $submission ? 'Actualizar evidencia y solicitar evaluación' : 'Enviar evidencia para evaluación' }}</button>
            </form>
        </section>
    </div>
@endsection
