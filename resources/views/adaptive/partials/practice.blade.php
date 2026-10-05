{{-- Práctica del tema ($topic, $practice, $attempts). La versión 2 trae objetivo, entrega, criterios, pistas y autochequeo. --}}
@php($brief = $practice->instructions ?? [])
@php($material = $practice->artifact ?? [])
@php($v2 = ($brief['version'] ?? 1) >= 2)
@php($kind = $material['kind'] ?? ($practice->type === 'excel_dataset' ? 'table' : (in_array($practice->type, ['code_bug', 'code_complete'], true) ? 'code' : 'text')))
@php($latest = $attempts->first())
<div class="max-w-3xl text-slate-700" x-data="{ storageKey: 'ag-practice-{{ $practice->id }}', hintsShown: 0, checked: [],
        init() { try { const saved = JSON.parse(localStorage.getItem(this.storageKey) || '{}'); this.hintsShown = saved.hintsShown || 0; this.checked = saved.checked || []; } catch (error) {} },
        save() { try { localStorage.setItem(this.storageKey, JSON.stringify({ hintsShown: this.hintsShown, checked: this.checked })); } catch (error) {} } }">
    <h3 class="text-balance text-xl font-extrabold text-slate-900">{{ $practice->title }}</h3>
    <p class="mt-3 leading-7">{{ $brief['context'] ?? '' }}</p>
    @if (filled($brief['objective'] ?? null))
        <p class="mt-3 leading-7"><span class="font-bold text-slate-900">Objetivo: </span>{{ $brief['objective'] }}</p>
    @endif

    {{-- Material de trabajo según su tipo: tabla, código o texto. --}}
    <div class="mt-6">
        @if ($kind === 'table')
            @php($rows = array_values(array_filter(preg_split('/\r\n|\n|\r/', trim((string) ($material['content'] ?? ''))), fn ($line) => trim($line) !== '')))
            @php($header = $rows ? str_getcsv(array_shift($rows)) : [])
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h4 class="font-extrabold text-slate-900">Datos de la práctica</h4>
                <a href="{{ route('adaptive.topic.practice.download', $topic) }}" class="inline-flex min-h-11 items-center rounded-xl border border-blue-200 px-4 text-sm font-bold text-blue-700 transition hover:border-blue-400">Descargar CSV para Excel</a>
            </div>
            <div class="mt-3 max-h-96 overflow-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-sm tabular-nums">
                    <thead class="sticky top-0 bg-slate-50"><tr>@foreach ($header as $cell)<th scope="col" class="whitespace-nowrap px-3 py-2 font-bold text-slate-900">{{ $cell }}</th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($rows as $row)
                            <tr>@foreach (str_getcsv($row) as $cell)<td class="whitespace-nowrap px-3 py-2">{{ $cell }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ count($rows) }} filas · {{ $material['label'] ?? 'datos.csv' }}</p>
        @elseif ($kind === 'code')
            <div class="flex flex-wrap items-center justify-between gap-3" x-data="{ copied: false }">
                <h4 class="font-extrabold text-slate-900">Código de la práctica <span class="font-semibold text-slate-500">· {{ $material['language'] ?? '' }}</span></h4>
                <button type="button" class="inline-flex min-h-11 items-center rounded-xl border border-blue-200 px-4 text-sm font-bold text-blue-700 transition hover:border-blue-400"
                    @click="navigator.clipboard?.writeText($refs.code.innerText).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" x-text="copied ? 'Copiado' : 'Copiar código'">Copiar código</button>
            </div>
            <pre class="mt-3 max-h-[28rem] overflow-auto rounded-xl bg-slate-950 p-4 text-sm leading-6 text-slate-100"><code x-ref="code">{{ $material['content'] ?? '' }}</code></pre>
        @else
            <h4 class="font-extrabold text-slate-900">{{ $material['label'] ?? 'Caso' }}</h4>
            <div class="mt-3 space-y-3 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 leading-7">
                @foreach (preg_split('/\n\s*\n/', trim((string) ($material['content'] ?? ''))) as $paragraph)
                    <p class="whitespace-pre-line">{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif
    </div>

    <h4 class="mt-8 font-extrabold text-slate-900">Qué debes hacer</h4>
    <ol class="mt-3 list-decimal space-y-2 pl-5 leading-7">
        @foreach ($brief['steps'] ?? [] as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ol>

    @if ($v2)
        <p class="mt-6 rounded-xl bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-950 dark:text-blue-50"><span class="font-bold">Qué entregas: </span>{{ $brief['deliverable'] ?? '' }}</p>

        <h4 class="mt-8 font-extrabold text-slate-900">Cómo se evaluará</h4>
        <p class="mt-1 text-sm text-slate-600">Marca cada criterio cuando creas que tu respuesta lo cumple.</p>
        <ul class="mt-3 space-y-2">
            @foreach ($brief['success_criteria'] ?? [] as $criterion)
                <li><label class="flex min-h-11 cursor-pointer items-start gap-3 py-1 leading-6">
                        <input type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" value="{{ $loop->index }}" x-model.number="checked" @change="save()">
                        <span>{{ $criterion }}</span></label></li>
            @endforeach
        </ul>

        @php($hints = $brief['hints'] ?? [])
        @if (count($hints))
            <div class="mt-8">
                <h4 class="font-extrabold text-slate-900">Pistas</h4>
                <p class="mt-1 text-sm text-slate-600">Ábrelas solo si te atascas: van de la más general a la más específica.</p>
                <ol class="mt-3 space-y-2">
                    @foreach ($hints as $hint)
                        <li x-show="hintsShown > {{ $loop->index }}" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-50"><span class="font-bold">Pista {{ $loop->iteration }}: </span>{{ $hint }}</li>
                    @endforeach
                </ol>
                <button type="button" x-show="hintsShown < {{ count($hints) }}" @click="hintsShown++; save()"
                    class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-blue-700 underline-offset-4 hover:underline"
                    x-text="hintsShown === 0 ? 'Ver la primera pista' : 'Ver la pista ' + (hintsShown + 1) + ' de {{ count($hints) }}'">Ver la primera pista</button>
            </div>
        @endif

        @if (count($brief['self_check'] ?? []))
            <h4 class="mt-8 font-extrabold text-slate-900">Antes de enviar, pregúntate</h4>
            <ul class="mt-3 list-disc space-y-1 pl-5 leading-7">
                @foreach ($brief['self_check'] as $question)
                    <li>{{ $question }}</li>
                @endforeach
            </ul>
        @endif
    @endif

    <form method="POST" action="{{ route('adaptive.topic.practice.submit', $topic) }}" class="mt-8">@csrf
        <label for="practice-answer" class="font-extrabold text-slate-900">{{ $latest ? 'Tu nuevo intento' : 'Tu respuesta' }}</label>
        <textarea id="practice-answer" required name="answer" rows="10" maxlength="12000"
            class="mt-3 w-full rounded-xl border-slate-200 text-sm leading-6 focus:border-blue-500 focus:ring-blue-500 {{ $kind === 'code' ? 'font-mono' : '' }}"
            placeholder="{{ $v2 ? Str::limit($brief['deliverable'] ?? '', 160) : 'Describe tu solución, pega tu resultado o comparte el código corregido.' }}">{{ old('answer') }}</textarea>
        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2">
            <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">{{ $latest ? 'Enviar nuevo intento' : 'Enviar mi práctica' }}</button>
            <p class="text-sm text-slate-600">El evaluador IA revisará cada criterio y te dirá qué mejorar. Puedes intentarlo las veces que quieras.</p>
        </div>
    </form>

    @include('adaptive.partials.practice-feedback', ['attempt' => $latest, 'attempts' => $attempts])
</div>
