{{-- Vista clásica de la ruta (la original): tarjetas completas, una por fase. Se conserva para poder volver a ella. --}}
    <div class="mt-8 space-y-5">
    @foreach($path->items as $item)
        @php($locked=$path->items->where('position','<',$item->position)->where('status','!=','completed')->isNotEmpty())
        <article class="rounded-2xl border {{ $item->status === 'completed' ? 'border-blue-200 bg-blue-50/50' : ($locked ? 'border-slate-100 bg-slate-50 opacity-60' : 'border-blue-200 bg-white') }} p-5 shadow-sm">
            <div class="flex flex-wrap items-start gap-4"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $item->status === 'completed' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700' }} font-black">{{ $item->status === 'completed' ? '✓' : $item->position }}</span><div class="min-w-0 flex-1"><h2 class="text-xl font-black text-slate-900">{{ $item->title }}</h2><p class="mt-1 text-sm font-semibold text-blue-600">{{ $item->area }}</p><p class="mt-1 text-sm text-slate-600">{{ $item->description }}</p><p class="mt-3 text-xs font-bold text-slate-500">{{ $item->topics->count() }} temas · contenido y práctica bajo demanda</p></div>@if($locked)<span class="text-xs font-bold text-slate-500">Bloqueado</span>@else<a href="{{ route('adaptive.path.item.show',$item) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">{{ $item->status === 'completed' ? 'Repasar módulo' : 'Ver temas' }}</a>@endif</div>
        </article>
    @endforeach
    </div>
