@extends('layouts.platform')

@section('content')
    <section class="mb-9 rounded-3xl bg-sky-950 px-6 py-10 text-white sm:px-9"><p class="text-sm font-semibold uppercase tracking-[0.16em] text-sky-300">Catálogo</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Encuentra tu próximo curso</h1><p class="mt-3 max-w-2xl text-sky-100/85">Explora contenido diseñado para aprender de forma práctica, paso a paso.</p></section>
    @if($courses->count() > 0)
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as $course)
                <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-900/10">
                    <img class="h-48 w-full object-cover" src="{{ $course->cover_image_url ?? 'https://via.placeholder.com/400x250.png/0369a1/ffffff?text=Curso' }}" alt="Imagen del curso {{ $course->title }}">
                    <div class="p-6"><p class="text-xs font-bold uppercase tracking-[0.14em] text-sky-700">Curso disponible</p><h2 class="mt-2 text-xl font-bold text-slate-900">{{ $course->title }}</h2><p class="mt-3 min-h-12 text-sm leading-6 text-slate-500">{{ Str::limit($course->description, 100) }}</p><a href="{{ route('public.course.detail', $course) }}" class="mt-6 block rounded-xl bg-sky-700 px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-sky-800">Ver detalles</a></div>
                </article>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><h2 class="text-xl font-bold text-slate-900">Aún no hay cursos disponibles.</h2><p class="mt-2 text-sm text-slate-500">Vuelve pronto para conocer las novedades.</p></div>
    @endif
@endsection
