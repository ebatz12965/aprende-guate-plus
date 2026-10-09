{{-- Vista «Compacta»: una fila por fase con su avance; al pasar el cursor, enfocarla o tocarla, se abre con toda su información. --}}
<div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @mouseleave="unhover('compact')">
    <div class="hidden grid-cols-[3rem_minmax(0,1fr)_11rem_7rem] gap-4 border-b border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500 sm:grid" aria-hidden="true">
        <span>Fase</span><span>Nombre</span><span>Temas</span><span class="text-right">Estado</span>
    </div>
    <ol class="divide-y divide-slate-200" aria-label="Fases de la ruta">
        @foreach ($phases as $phase)
            @php($item = $phase['item'])
            @php($i = $phase['index'])
            <li @mouseenter="hover('compact', {{ $i }})" class="transition-colors duration-200" :class="isOpen('compact', {{ $i }}) ? 'bg-blue-50/40' : ''">
                <h2>
                    <button type="button" id="compact-{{ $i }}" @click="toggle('compact', {{ $i }})" @focus="focusOpen('compact', {{ $i }})" :aria-expanded="isOpen('compact', {{ $i }}).toString()" aria-controls="compact-panel-{{ $i }}"
                        class="grid w-full grid-cols-[3rem_minmax(0,1fr)_auto] items-center gap-4 px-4 py-3 text-left outline-none focus-visible:bg-blue-50 sm:grid-cols-[3rem_minmax(0,1fr)_11rem_7rem] {{ $phase['locked'] ? 'opacity-70' : '' }}">
                        <span class="grid h-9 w-9 place-items-center rounded-full text-sm font-extrabold tabular-nums {{ $phase['completed'] ? 'bg-blue-600 text-white' : ($phase['current'] ? 'bg-white text-blue-700 ring-2 ring-blue-600' : 'bg-slate-100 text-slate-600') }}" aria-hidden="true">
                            @if ($phase['completed'])<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>@else{{ $item->position }}@endif
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-bold text-slate-900">{{ $item->title }}</span>
                            <span class="block truncate text-xs font-semibold text-slate-500 sm:hidden">{{ $phase['topicsDone'] }} de {{ $phase['topics'] }} temas</span>
                        </span>
                        <span class="hidden items-center gap-2 sm:flex">
                            <span class="h-1.5 flex-1 rounded-full bg-slate-100"><span class="block h-full rounded-full bg-blue-600" style="width: {{ $phase['topics'] ? round($phase['topicsDone'] / $phase['topics'] * 100) : 0 }}%"></span></span>
                            <span class="w-10 text-right text-xs font-bold tabular-nums text-slate-600">{{ $phase['topicsDone'] }}/{{ $phase['topics'] }}</span>
                        </span>
                        <span class="text-right text-xs font-bold {{ $phase['completed'] ? 'text-blue-700' : ($phase['current'] ? 'text-blue-700' : 'text-slate-500') }}">
                            {{ $phase['completed'] ? 'Completada' : ($phase['current'] ? 'Estás aquí' : ($phase['locked'] ? 'Bloqueada' : 'Disponible')) }}
                        </span>
                    </button>
                </h2>
                <div id="compact-panel-{{ $i }}" role="region" aria-labelledby="compact-{{ $i }}" class="grid transition-[grid-template-rows,opacity] duration-300 ease-[cubic-bezier(.16,1,.3,1)] motion-reduce:transition-none"
                    :class="isOpen('compact', {{ $i }}) ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'" :inert="! isOpen('compact', {{ $i }})">
                    <div class="overflow-hidden"><div class="max-w-3xl pb-4 pl-[4.75rem] pr-4">@include('adaptive.partials.path.phase-detail')</div></div>
                </div>
            </li>
        @endforeach
    </ol>
</div>
