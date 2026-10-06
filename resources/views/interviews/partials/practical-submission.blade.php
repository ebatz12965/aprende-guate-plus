{{-- Lo que la persona entregó en la prueba práctica, junto al caso. --}}
@php
    $answers = $stage->answers ?? [];
@endphp
<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="entrega">
    <h2 id="entrega" class="text-lg font-extrabold text-slate-900">Tu entrega</h2>
    @if ($answers['late'] ?? false)<p class="mt-1 text-sm font-semibold text-amber-800">La entregaste después del tiempo.</p>@endif
    @if (filled($answers['text'] ?? null))
        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $answers['text'] }}</p>
    @endif
    @if (! empty($answers['file']))
        <a href="{{ route('adaptive.interviews.stage.attachment', [$simulation, $stage]) }}" class="mt-3 inline-flex min-h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:border-blue-400">Descargar {{ $answers['file']['name'] }}</a>
    @endif
    <details class="mt-5 rounded-xl bg-slate-50 p-4">
        <summary class="cursor-pointer text-sm font-bold text-slate-700">Ver el caso</summary>
        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $stage->content['context'] ?? '' }}</p>
        <pre class="mt-2 overflow-x-auto whitespace-pre-wrap font-mono text-xs leading-5 text-slate-700">{{ $stage->content['materials'] ?? '' }}</pre>
    </details>
</section>
