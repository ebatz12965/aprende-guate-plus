@extends('layouts.platform')
@section('content')
<div class="mx-auto max-w-5xl">
@if(!$path)
    <div class="platform-feature"><h1 class="!mt-0 text-3xl">Tu ruta aún no está lista</h1><p class="mt-3 text-slate-600">Completa el diagnóstico para crear una ruta con módulos y temas según tu meta.</p><a href="{{ route('adaptive.diagnostic') }}" class="mt-5 inline-block rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white">Hacer diagnóstico</a></div>
@else
    <p class="text-sm font-extrabold uppercase tracking-wide text-indigo-600">Ruta personalizada · {{ $path->estimated_level ?? 'Inicial' }}</p>
    <h1 class="mt-2 text-3xl font-black text-slate-900">{{ $path->title }}</h1>
    <p class="mt-2 max-w-3xl text-slate-600">{{ $path->goal }} · {{ $path->estimated_hours ? $path->estimated_hours.' horas estimadas' : 'Avance a tu ritmo' }}</p>
    <p class="mt-5 rounded-xl bg-indigo-50 px-4 py-3 text-sm text-indigo-900">La ruta se diseñó primero. Al abrir cada tema podrás generar su lección y su práctica especializada sin esperar a que toda la plataforma cargue contenido innecesario.</p>
    <div class="mt-8 space-y-5">
    @foreach($path->items as $item)
        @php($locked=$path->items->where('position','<',$item->position)->where('status','!=','completed')->isNotEmpty())
        <article class="rounded-2xl border {{ $item->status === 'completed' ? 'border-emerald-200 bg-emerald-50/50' : ($locked ? 'border-slate-100 bg-slate-50 opacity-60' : 'border-indigo-200 bg-white') }} p-5 shadow-sm">
            <div class="flex flex-wrap items-start gap-4"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $item->status === 'completed' ? 'bg-emerald-500 text-white' : 'bg-indigo-100 text-indigo-700' }} font-black">{{ $item->status === 'completed' ? '✓' : $item->position }}</span><div class="min-w-0 flex-1"><p class="text-xs font-bold uppercase tracking-wide text-indigo-600">{{ $item->area }}</p><h2 class="mt-1 text-xl font-black text-slate-900">{{ $item->title }}</h2><p class="mt-1 text-sm text-slate-600">{{ $item->description }}</p><p class="mt-3 text-xs font-bold text-slate-500">{{ $item->topics->count() }} temas · contenido y práctica bajo demanda</p></div>@if($locked)<span class="text-xs font-bold text-slate-400">Bloqueado</span>@else<a href="{{ route('adaptive.path.item.show',$item) }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">{{ $item->status === 'completed' ? 'Repasar módulo' : 'Ver temas' }}</a>@endif</div>
        </article>
    @endforeach
    </div>
@endif
</div>
@endsection
