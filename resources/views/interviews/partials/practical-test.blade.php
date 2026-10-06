{{-- Prueba práctica en curso: caso, materiales, criterios y entrega por escrito o con un archivo. --}}
@php
    $case = $stage->content ?? [];
    $blocks = \App\Services\Interviews\PracticalTestService::materialBlocks($case['materials'] ?? '');
    $deadline = $stage->deadline();
@endphp
<section class="mt-6 learning-panel" aria-labelledby="caso">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <h2 id="caso" class="text-lg font-extrabold text-slate-900">El caso</h2>
        <p class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold tabular-nums text-slate-700" x-data="{ left: '', late: false, deadline: @js($deadline?->toIso8601String()), tick() { if (!this.deadline) return; const s = Math.floor((new Date(this.deadline) - new Date()) / 1000); this.late = s <= 0; const a = Math.abs(s); this.left = `${String(Math.floor(a / 60)).padStart(2, '0')}:${String(a % 60).padStart(2, '0')}`; } }" x-init="tick(); setInterval(() => tick(), 1000)" role="timer">
            <span x-show="! late">Tiempo: <span x-text="left"></span></span>
            <span x-show="late" x-cloak class="text-amber-800">Tiempo cumplido</span>
        </p>
    </div>
    <p class="mt-3 leading-7 text-slate-700">{{ $case['context'] ?? '' }}</p>
    <h3 class="mt-5 font-extrabold text-slate-900">Qué debes hacer</h3>
    <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-slate-700">
        @foreach ($case['instructions'] ?? [] as $step)<li>{{ $step }}</li>@endforeach
    </ol>
    <h3 class="mt-5 font-extrabold text-slate-900">Materiales</h3>
    <div class="mt-2 space-y-3">
        @foreach ($blocks as $block)
            @if ($block['type'] === 'table')
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50"><tr>@foreach ($block['rows'][0] as $cell)<th scope="col" class="whitespace-nowrap px-3 py-2 text-left font-bold text-slate-700">{{ $cell }}</th>@endforeach</tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach (array_slice($block['rows'], 1) as $row)
                                <tr>@foreach ($row as $cell)<td class="px-3 py-2 align-top text-slate-700">{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <pre class="overflow-x-auto whitespace-pre-wrap rounded-xl bg-slate-50 p-4 font-mono text-sm leading-6 text-slate-800">{{ trim($block['text']) }}</pre>
            @endif
        @endforeach
    </div>
    <h3 class="mt-5 font-extrabold text-slate-900">Qué entregas</h3>
    <p class="mt-1 text-slate-700">{{ $case['deliverable'] ?? '' }}</p>
    <h3 class="mt-5 font-extrabold text-slate-900">Cómo se evalúa</h3>
    <ul class="mt-2 space-y-1.5">
        @foreach ($case['criteria'] ?? [] as $criterion)
            <li class="flex gap-2 text-sm text-slate-700"><span class="shrink-0 font-bold tabular-nums text-slate-900">{{ $criterion['weight'] }} %</span><span><span class="font-semibold">{{ $criterion['name'] }}:</span> {{ $criterion['description'] }}</span></li>
        @endforeach
    </ul>
</section>

<form method="POST" action="{{ route('adaptive.interviews.stage.submit', [$simulation, $stage]) }}" enctype="multipart/form-data" class="mt-6 learning-panel space-y-4"
    x-data="{ answer: '', key: 'ag-practical-{{ $stage->id }}', sending: false, init() { try { this.answer = localStorage.getItem(this.key) || ''; } catch (e) {} }, save() { try { localStorage.setItem(this.key, this.answer); } catch (e) {} } }"
    @submit="sending = true; try { localStorage.removeItem(key) } catch (e) {}">
    @csrf
    <div>
        <label for="answer" class="block font-extrabold text-slate-900">Tu solución</label>
        <textarea id="answer" name="answer" rows="12" maxlength="{{ \App\Services\Interviews\PracticalTestService::MAX_ANSWER }}" x-model="answer" @input="save()"
            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Escribe tu solución paso a paso. Si preparaste un archivo, puedes adjuntarlo abajo.">{{ old('answer') }}</textarea>
        <p class="mt-1 text-xs text-slate-500"><span x-text="answer.length"></span>/{{ \App\Services\Interviews\PracticalTestService::MAX_ANSWER }} · el borrador se guarda en este navegador</p>
        @error('answer')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="attachment" class="block text-sm font-semibold text-slate-700">Archivo <span class="font-normal text-slate-500">(opcional · PDF, Word, Excel, PowerPoint, texto o CSV · máximo 10 MB)</span></label>
        <input id="attachment" name="attachment" type="file" accept=".pdf,.txt,.csv,.xlsx,.docx,.pptx" class="mt-2 block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-bold file:text-blue-800">
        @error('attachment')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
    <div class="flex justify-end">
        <button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50" :disabled="sending" x-text="sending ? 'Entregando…' : 'Entregar la prueba'"></button>
    </div>
</form>
