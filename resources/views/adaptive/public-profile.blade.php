@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-4xl">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-sky-800 to-indigo-700 px-6 py-8 text-white sm:px-10">
            <div class="flex items-center gap-4">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-white/15 text-2xl font-extrabold">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span>
                <div><h1 class="text-3xl font-extrabold">{{ $user->name }}</h1><p class="mt-1 text-sky-100">{{ $profile->professional_goal ?: 'Miembro de Aprende Guate' }}</p></div>
            </div>
        </div>
        <div class="space-y-8 p-6 sm:p-10">
            @if($profile->linkedin_url)<a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-xl border border-indigo-200 px-4 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-50">Ver LinkedIn</a>@endif

            @if($profile->bio_visibility === 'public' && $profile->bio)<section><h2 class="text-xl font-extrabold">Sobre mí</h2><p class="mt-3 whitespace-pre-line leading-7 text-slate-600">{{ $profile->bio }}</p></section>@endif

            @if($profile->education_visibility === 'public' && count($profile->education ?? []))<section><h2 class="text-xl font-extrabold">Escolaridad</h2><div class="mt-3 space-y-3">@foreach($profile->education as $item)<div class="rounded-xl border border-slate-200 p-4"><p class="font-bold">{{ $item['program'] ?? 'Estudio' }}</p><p class="text-sm text-slate-600">{{ $item['institution'] ?? '' }}{{ !empty($item['period']) ? ' · '.$item['period'] : '' }}</p></div>@endforeach</div></section>@endif

            @if($profile->work_experience_visibility === 'public' && count($profile->work_experience ?? []))<section><h2 class="text-xl font-extrabold">Experiencia laboral</h2><div class="mt-3 space-y-3">@foreach($profile->work_experience as $item)<div class="rounded-xl border border-slate-200 p-4"><p class="font-bold">{{ $item['role'] ?? 'Experiencia' }}</p><p class="text-sm text-slate-600">{{ $item['company'] ?? '' }}{{ !empty($item['period']) ? ' · '.$item['period'] : '' }}</p>@if(!empty($item['description']))<p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $item['description'] }}</p>@endif</div>@endforeach</div></section>@endif

            <section><h2 class="text-xl font-extrabold">Rutas completadas en Aprende Guate</h2><div class="mt-3 grid gap-3 md:grid-cols-2">@forelse($completedPaths as $path)<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4"><p class="font-bold">{{ $path->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $path->goal }}</p><p class="mt-2 text-xs font-bold text-emerald-700">Completada {{ $path->updated_at->format('d/m/Y') }}</p></div>@empty<p class="text-sm text-slate-500">Aún no hay rutas completadas.</p>@endforelse</div></section>
        </div>
    </section>
</div>
@endsection
