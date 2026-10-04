{{-- Retroalimentación del último intento de práctica ($attempt). --}}
@if($attempt)
    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-extrabold text-slate-800">Tu última entrega</p>
            <p class="text-xs text-slate-500">{{ $attempt->submitted_at?->format('d/m/Y H:i') }}</p>
        </div>

        @if($attempt->status === 'evaluated')
            <p class="mt-3 text-2xl font-extrabold text-blue-700">{{ $attempt->score }}<span class="text-sm font-bold text-slate-500">/100</span></p>
            <p class="mt-2 leading-6 text-slate-700">{{ $attempt->feedback }}</p>
            @if(count(data_get($attempt->evaluation, 'strengths', [])))
                <p class="mt-3 font-bold text-emerald-700">Lo que hiciste bien</p>
                <ul class="mt-1 list-disc space-y-1 pl-5 text-slate-700">@foreach(data_get($attempt->evaluation, 'strengths', []) as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
            @if(count(data_get($attempt->evaluation, 'improvements', [])))
                <p class="mt-3 font-bold text-amber-700">Para mejorar</p>
                <ul class="mt-1 list-disc space-y-1 pl-5 text-slate-700">@foreach(data_get($attempt->evaluation, 'improvements', []) as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
            <p class="mt-3 text-xs text-slate-500">Puedes volver a entregar para mejorar tu puntaje.</p>
        @elseif(in_array($attempt->status, ['queued', 'evaluating'], true))
            <p class="ag-soft-pulse mt-3 flex items-center gap-2 text-blue-700"><span class="ag-spinner"></span>El evaluador IA está revisando tu entrega. Recarga la página en unos momentos.</p>
        @else
            <p class="mt-3 leading-6 text-slate-600">{{ $attempt->feedback ?? 'Tu práctica quedó registrada.' }}</p>
        @endif
    </div>
@endif
