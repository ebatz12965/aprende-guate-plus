{{-- Pestañas del panel: cada una es su propia página, así el tablero de IA puede abrirse solo (por ejemplo, para proyectarlo). --}}
@php($tabs = [['admin.dashboard', 'Administrativo'], ['admin.ai-pulse.index', 'Inteligencia artificial']])
<nav aria-label="Vistas del panel" class="mb-7 flex gap-2 overflow-x-auto border-b border-slate-200">
    @foreach ($tabs as [$route, $label])
        @php($active = request()->routeIs($route))
        <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
            class="-mb-px inline-flex min-h-12 shrink-0 items-center gap-2 border-b-2 px-4 text-base font-extrabold transition {{ $active ? 'border-blue-600 text-slate-900' : 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900' }}">
            {{ $label }}
            @if ($route === 'admin.ai-pulse.index')<span class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700 sm:inline-flex"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>En vivo</span>@endif
        </a>
    @endforeach
</nav>
