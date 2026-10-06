{{-- Resultado de la prueba de razonamiento: calificación exacta y revisión de cada pregunta. --}}
@php
    $grade = $stage->evaluation;
    $items = collect($stage->content['items'] ?? []);
@endphp
<section class="mt-6 learning-panel" aria-labelledby="resultado">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="grid h-24 w-24 shrink-0 place-items-center rounded-2xl {{ $stage->score >= 70 ? 'bg-emerald-50 text-emerald-800' : ($stage->score >= 50 ? 'bg-amber-50 text-amber-800' : 'bg-rose-50 text-rose-800') }}">
            <p class="text-center"><span class="block text-3xl font-black tabular-nums">{{ $stage->score }}</span><span class="text-xs font-bold">de 100</span></p>
        </div>
        <div>
            <h2 id="resultado" class="text-xl font-extrabold text-slate-900">Tu resultado</h2>
            <p class="mt-1 leading-7 text-slate-700">{{ $grade['summary'] }}</p>
            @if ($stage->answers['late'] ?? false)<p class="mt-1 text-sm font-semibold text-amber-800">La entregaste después del tiempo: en una prueba real, las respuestas fuera de tiempo no cuentan.</p>@endif
        </div>
    </div>
    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
        @foreach ($grade['categories'] as $category)
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-sm font-bold text-slate-900">{{ $category['name'] }}</dt>
                <dd class="mt-1 text-2xl font-extrabold tabular-nums text-slate-900">{{ $category['correct'] }} <span class="text-base font-semibold text-slate-500">de {{ $category['total'] }}</span></dd>
            </div>
        @endforeach
    </dl>
    <p class="mt-4 text-sm text-slate-500">Es una práctica: no es un test psicométrico validado ni mide tu personalidad.</p>
</section>

<section class="mt-6 learning-panel" aria-labelledby="revision">
    <h2 id="revision" class="text-lg font-extrabold text-slate-900">Revisión de cada pregunta</h2>
    <ol class="mt-4 space-y-5">
        @foreach ($items as $item)
            @php
                $result = $grade['items'][$item['id']] ?? ['chosen' => null, 'correct' => false];
            @endphp
            <li class="border-t border-slate-100 pt-5 first:border-t-0 first:pt-0">
                <p class="text-xs font-bold {{ $result['correct'] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $result['correct'] ? '✓ Correcta' : ($result['chosen'] === null ? '✗ Sin responder' : '✗ Incorrecta') }} · {{ \App\Services\Interviews\ReasoningTestService::CATEGORIES[$item['category']] ?? '' }}</p>
                @if (! empty($item['passage']))<blockquote class="mt-2 rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-600">{{ $item['passage'] }}</blockquote>@endif
                <p class="mt-2 font-semibold leading-7 text-slate-900"><span class="text-slate-500">{{ substr($item['id'], 1) }}.</span> {{ $item['prompt'] }}</p>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($item['options'] as $index => $option)
                        <li class="rounded-lg px-3 py-2 {{ $index === $item['answer'] ? 'bg-emerald-50 font-semibold text-emerald-900' : ($index === $result['chosen'] ? 'bg-rose-50 text-rose-900' : 'text-slate-600') }}">
                            {{ $option }}
                            @if ($index === $item['answer']) <span class="text-xs">(correcta)</span>@elseif ($index === $result['chosen']) <span class="text-xs">(tu respuesta)</span>@endif
                        </li>
                    @endforeach
                </ul>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['explanation'] }}</p>
            </li>
        @endforeach
    </ol>
</section>

@if ($next)
    <div class="mt-6 flex justify-end">
        <a href="{{ route('adaptive.interviews.stage', [$simulation, $next]) }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">Siguiente etapa: {{ $next->title }} →</a>
    </div>
@endif
