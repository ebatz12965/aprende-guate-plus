@extends('layouts.platform')

@section('content')
@php($photoUrl = $profile->photo_path ? Storage::disk('public')->url($profile->photo_path) : null)
<div class="mx-auto w-full max-w-5xl">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-slate-950 via-blue-950 to-blue-800 px-6 py-9 text-white sm:px-10 sm:py-11">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-5">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Foto de {{ $user->name }}" class="h-24 w-24 shrink-0 rounded-2xl border-4 border-white/25 object-cover shadow-xl">
                    @else
                        <span class="grid h-24 w-24 shrink-0 place-items-center rounded-2xl border-4 border-white/20 bg-white/10 text-3xl font-extrabold">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span>
                    @endif
                    <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-200">Perfil profesional</p><h1 class="mt-1 truncate text-3xl font-extrabold sm:text-4xl">{{ $user->name }}</h1><p class="mt-2 text-base text-blue-100">{{ $profile->professional_goal ?: 'Miembro de Aprende Guate' }}</p></div>
                </div>
                @if($profile->linkedin_url)<a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/20">LinkedIn ↗</a>@endif
            </div>
        </div>

        <div class="grid gap-8 p-6 sm:p-10 lg:grid-cols-[minmax(0,1.25fr)_minmax(260px,.75fr)]">
            <div class="space-y-8">
                @if($profile->bio_visibility === 'public' && $profile->bio)
                    <section><p class="text-xs font-extrabold uppercase tracking-[0.14em] text-blue-700">Sobre mí</p><p class="mt-3 whitespace-pre-line text-base leading-8 text-slate-600">{{ $profile->bio }}</p></section>
                @endif

                @if($profile->work_experience_visibility === 'public' && count($profile->work_experience ?? []))
                    <section><div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-100 text-sm font-extrabold text-blue-800">04</span><h2 class="text-xl font-extrabold text-slate-900">Experiencia</h2></div><div class="mt-4 space-y-3">@foreach($profile->work_experience as $item)<article class="rounded-xl border border-slate-200 p-5"><p class="font-extrabold text-slate-900">{{ $item['role'] ?? 'Experiencia' }}</p><p class="mt-1 text-sm font-semibold text-blue-700">{{ $item['company'] ?? '' }}</p>@if(!empty($item['period']))<p class="mt-2 text-sm text-slate-500">{{ $item['period'] }}</p>@endif @if(!empty($item['description']))<p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $item['description'] }}</p>@endif</article>@endforeach</div></section>
                @endif
            </div>

            <aside class="space-y-7 lg:border-l lg:border-slate-200 lg:pl-8">
                @if($profile->education_visibility === 'public' && count($profile->education ?? []))
                    <section><div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-100 text-sm font-extrabold text-blue-800">03</span><h2 class="text-lg font-extrabold text-slate-900">Escolaridad</h2></div><div class="mt-4 space-y-3">@foreach($profile->education as $item)<article class="rounded-xl bg-slate-50 p-4"><p class="font-bold text-slate-900">{{ $item['program'] ?? 'Estudio' }}</p><p class="mt-1 text-sm text-slate-600">{{ $item['institution'] ?? '' }}</p>@if(!empty($item['period']))<p class="mt-2 text-xs font-bold text-slate-500">{{ $item['period'] }}</p>@endif</article>@endforeach</div></section>
                @endif

                <section><div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-100 text-sm font-extrabold text-emerald-800">✓</span><h2 class="text-lg font-extrabold text-slate-900">Rutas completadas</h2></div><div class="mt-4 space-y-3">@forelse($completedPaths as $path)<article class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4"><p class="font-bold text-slate-900">{{ $path->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $path->goal }}</p><p class="mt-3 text-xs font-bold text-emerald-700">{{ $path->updated_at->format('d/m/Y') }}</p></article>@empty<p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Aún no hay rutas completadas.</p>@endforelse</div></section>
            </aside>
        </div>
    </section>
</div>
@endsection