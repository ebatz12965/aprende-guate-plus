{{--
    Vista «Recorrido»: la ruta como un trazo con estaciones. Cada fase se ve resumida y, al pasar el cursor,
    enfocarla o tocarla, se abre con toda su información. El tramo ya recorrido de la línea va en azul.
--}}
<ol class="relative mt-8" aria-label="Fases de la ruta">
    @foreach ($phases as $phase)
        @php
            $item = $phase['item'];
            $i = $phase['index'];
            $left = $i % 2 === 1;
            $lineDone = $phase['completed'];
        @endphp
        <li class="relative grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-4 pb-4 md:grid-cols-[minmax(0,1fr)_3.5rem_minmax(0,1fr)] md:gap-x-6"
            @mouseenter="hover('trail', {{ $i }})" @mouseleave="unhover('trail')">
            {{-- Tramo de la línea hacia la siguiente estación --}}
            @unless ($loop->last)
                <span class="absolute top-11 bottom-0 left-[1.3125rem] w-0.5 md:left-1/2 md:-translate-x-1/2 {{ $lineDone ? 'bg-blue-600' : 'bg-[linear-gradient(to_bottom,#CBD5E1_50%,transparent_0)] bg-[length:2px_8px]' }}" aria-hidden="true"></span>
            @endunless

            {{-- Estación --}}
            <span class="relative z-10 col-start-1 row-start-1 grid h-11 w-11 place-items-center self-start rounded-full text-sm font-extrabold tabular-nums transition-transform duration-300 ease-[cubic-bezier(.16,1,.3,1)] motion-reduce:transition-none md:col-start-2 md:justify-self-center
                {{ $phase['completed'] ? 'bg-blue-600 text-white' : ($phase['current'] ? 'bg-white text-blue-700 ring-[3px] ring-blue-600' : ($phase['locked'] ? 'bg-slate-100 text-slate-500 ring-1 ring-slate-200' : 'bg-blue-100 text-blue-800')) }}"
                :class="isOpen('trail', {{ $i }}) ? 'scale-110' : ''" aria-hidden="true">
                @if ($phase['completed'])
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                @elseif ($phase['locked'])
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                @else
                    {{ $item->position }}
                @endif
                @if ($phase['current'])<span class="absolute inset-0 animate-ping rounded-full ring-2 ring-blue-400 [animation-duration:2.4s] motion-reduce:hidden"></span>@endif
            </span>

            {{-- Tarjeta: resumida; se abre con toda la información --}}
            <article class="col-start-2 row-start-1 min-w-0 rounded-2xl border bg-white shadow-sm transition-[box-shadow,border-color] duration-300 {{ $left ? 'md:col-start-1 md:text-right' : 'md:col-start-3' }}
                {{ $phase['current'] ? 'border-blue-300' : 'border-slate-200' }} {{ $phase['locked'] ? 'opacity-75' : '' }}"
                :class="isOpen('trail', {{ $i }}) ? 'shadow-[0_12px_32px_-16px_rgba(2,6,23,0.35)] border-blue-300' : ''">
                <h2 class="text-base font-extrabold leading-6 text-slate-900">
                    <button type="button" id="trail-{{ $i }}" @click="toggle('trail', {{ $i }})" @focus="focusOpen('trail', {{ $i }})" :aria-expanded="isOpen('trail', {{ $i }}).toString()" aria-controls="trail-panel-{{ $i }}"
                        class="w-full rounded-2xl px-4 py-3 outline-none focus-visible:ring-2 focus-visible:ring-blue-500 text-left {{ $left ? 'md:text-right' : '' }}">
                        <span class="flex items-center gap-2 {{ $left ? 'md:flex-row-reverse' : '' }}">
                            <span class="min-w-0 flex-1">{{ $item->title }}</span>
                            @if ($phase['current'])<span class="shrink-0 rounded-full bg-blue-600 px-2.5 py-0.5 text-xs font-bold text-white">Estás aquí</span>@endif
                        </span>
                        <span class="mt-0.5 block text-xs font-semibold text-slate-500">
                            Fase {{ $item->position }} · {{ $phase['topicsDone'] }} de {{ $phase['topics'] }} {{ $phase['topics'] === 1 ? 'tema' : 'temas' }}{{ $phase['completed'] ? ' · completada' : ($phase['locked'] ? ' · bloqueada' : '') }}
                        </span>
                    </button>
                </h2>
                <div id="trail-panel-{{ $i }}" role="region" aria-labelledby="trail-{{ $i }}" class="grid transition-[grid-template-rows,opacity] duration-300 ease-[cubic-bezier(.16,1,.3,1)] motion-reduce:transition-none"
                    :class="isOpen('trail', {{ $i }}) ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'" :inert="! isOpen('trail', {{ $i }})">
                    <div class="overflow-hidden {{ $left ? 'md:text-left' : '' }}"><div class="px-4 pb-4">@include('adaptive.partials.path.phase-detail')</div></div>
                </div>
            </article>
        </li>

        {{-- Retos opcionales que se desbloquean al llegar a este punto --}}
        @foreach ($milestones->get($i, collect()) as $milestone)
            <li class="relative grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-4 pb-4 md:grid-cols-[minmax(0,1fr)_3.5rem_minmax(0,1fr)] md:gap-x-6">
                @unless ($loop->parent->last)<span class="absolute top-0 bottom-0 left-[1.3125rem] w-0.5 md:left-1/2 md:-translate-x-1/2 {{ $lineDone ? 'bg-blue-600' : 'bg-[linear-gradient(to_bottom,#CBD5E1_50%,transparent_0)] bg-[length:2px_8px]' }}" aria-hidden="true"></span>@endunless
                <span class="relative z-10 col-start-1 grid h-8 w-8 place-items-center self-center justify-self-center rounded-lg {{ $milestone['reached'] ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-300' : 'bg-slate-50 text-slate-400 ring-1 ring-slate-200' }} md:col-start-2" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21V4m0 0h11l-2 4 2 4H5"/></svg>
                </span>
                <p class="col-start-2 self-center text-sm {{ $i % 2 === 0 ? 'md:col-start-1 md:text-right' : 'md:col-start-3' }}">
                    @if ($milestone['reached'] && $milestone['challenge'])
                        <a href="{{ route('adaptive.challenges.show', $milestone['challenge']) }}" class="font-bold text-blue-700 underline-offset-4 hover:underline">Reto del {{ $milestone['percent'] }} % desbloqueado</a>
                        <span class="text-slate-500"> · opcional</span>
                    @else
                        <span class="font-semibold text-slate-500">Reto opcional del {{ $milestone['percent'] }} %</span>
                    @endif
                </p>
            </li>
        @endforeach
    @endforeach
</ol>
