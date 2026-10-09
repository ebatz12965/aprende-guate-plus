{{-- Detalle de una fase (la misma información de la vista clásica), dentro del panel que se expande. --}}
@php($item = $phase['item'])
<div class="space-y-3 pt-3">
    @if ($item->area)<p class="text-sm font-semibold text-blue-700">{{ $item->area }}</p>@endif
    @if ($item->description)<p class="text-sm leading-6 text-slate-600">{{ $item->description }}</p>@endif
    <div>
        <div class="flex items-baseline justify-between gap-3 text-xs font-bold text-slate-500">
            <span>{{ $phase['topics'] }} {{ $phase['topics'] === 1 ? 'tema' : 'temas' }} · contenido y práctica bajo demanda</span>
            <span class="tabular-nums">{{ $phase['topicsDone'] }}/{{ $phase['topics'] }}</span>
        </div>
        <div class="mt-1.5 h-1.5 rounded-full bg-slate-100" role="progressbar" aria-label="Temas completados de {{ $item->title }}" aria-valuemin="0" aria-valuemax="{{ $phase['topics'] }}" aria-valuenow="{{ $phase['topicsDone'] }}">
            <div class="h-full rounded-full bg-blue-600" style="width: {{ $phase['topics'] ? round($phase['topicsDone'] / $phase['topics'] * 100) : 0 }}%"></div>
        </div>
    </div>
    @if ($phase['locked'])
        <p class="flex items-center gap-2 text-sm font-semibold text-slate-500">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            Se desbloquea al aprobar el mini-quiz de la fase anterior.
        </p>
    @else
        <a href="{{ route('adaptive.path.item.show', $item) }}" class="inline-flex min-h-10 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700">{{ $phase['completed'] ? 'Repasar módulo' : 'Ver temas' }}</a>
    @endif
</div>
