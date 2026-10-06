{{-- Retroalimentación del último intento de práctica ($attempt) y el historial ($attempts). --}}
@if ($attempt)
    @php($attempts = $attempts ?? collect([$attempt]))
    <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm" id="retroalimentacion"
        @if (in_array($attempt->status, ['queued', 'evaluating'], true)) x-data x-init="setTimeout(() => window.location.reload(), 5000)" @endif>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h4 class="text-base font-extrabold text-slate-900">Tu intento {{ $attempts->count() }}</h4>
            <p class="text-xs text-slate-500">{{ $attempt->submitted_at?->locale('es')->translatedFormat('j \d\e F, g:i a') }}</p>
        </div>

        @if ($attempt->status === 'evaluated')
            <p class="mt-3 text-3xl font-extrabold tabular-nums text-slate-900">{{ $attempt->score }}<span class="text-base font-bold text-slate-500"> de 100</span></p>
            @if (filled(data_get($attempt->evaluation, 'progress')))
                <p class="mt-2 font-semibold leading-6 text-blue-800">{{ data_get($attempt->evaluation, 'progress') }}</p>
            @endif
            <p class="mt-2 leading-6 text-slate-700">{{ $attempt->feedback }}</p>

            @if (count(data_get($attempt->evaluation, 'criteria', [])))
                <h5 class="mt-5 font-bold text-slate-900">Criterio por criterio</h5>
                <ul class="mt-2 space-y-3">
                    @foreach (data_get($attempt->evaluation, 'criteria', []) as $criterion)
                        <li class="flex gap-3">
                            @if ($criterion['met'] ?? false)
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
                            @else
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
                            @endif
                            <div class="leading-6"><p class="font-semibold text-slate-900">{{ $criterion['criterion'] ?? '' }}<span class="sr-only">{{ ($criterion['met'] ?? false) ? ': cumplido' : ': pendiente' }}</span></p><p class="text-slate-600">{{ $criterion['comment'] ?? '' }}</p></div>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (count(data_get($attempt->evaluation, 'strengths', [])))
                <h5 class="mt-5 font-bold text-emerald-700">Lo que hiciste bien</h5>
                <ul class="mt-1 list-disc space-y-1 pl-5 leading-6 text-slate-700">@foreach (data_get($attempt->evaluation, 'strengths', []) as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
            @if (count(data_get($attempt->evaluation, 'improvements', [])))
                <h5 class="mt-5 font-bold text-amber-700">Para tu siguiente intento</h5>
                <ul class="mt-1 list-disc space-y-1 pl-5 leading-6 text-slate-700">@foreach (data_get($attempt->evaluation, 'improvements', []) as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
        @elseif (in_array($attempt->status, ['queued', 'evaluating'], true))
            <p class="mt-3 flex items-center gap-2 text-blue-700" role="status"><span class="ag-spinner"></span>El evaluador IA está revisando tu entrega. Esta sección se actualizará sola.</p>
        @else
            <p class="mt-3 leading-6 text-slate-600">{{ $attempt->feedback ?? 'Tu práctica quedó registrada.' }}</p>
        @endif

        @if ($attempts->count() > 1)
            <h5 class="mt-6 font-bold text-slate-900">Tus intentos</h5>
            <ol class="mt-2 flex flex-wrap gap-2" aria-label="Puntaje de cada intento, del más reciente al primero">
                @foreach ($attempts as $previous)
                    <li class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 tabular-nums">Intento {{ $attempts->count() - $loop->index }}: <span class="font-bold text-slate-900">{{ $previous->status === 'evaluated' ? $previous->score : 'en revisión' }}</span></li>
                @endforeach
            </ol>
        @endif
    </div>
@endif
