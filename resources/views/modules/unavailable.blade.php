@extends('layouts.platform')

@section('content')
<div class="mx-auto max-w-2xl py-10">
    <div class="rounded-2xl border border-amber-200 bg-white p-8 shadow-sm sm:p-10">
        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-sm font-bold text-amber-800 ring-1 ring-inset ring-amber-200">En mantenimiento</span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900">{{ $module['label'] }} no está disponible por ahora</h1>
        <p class="mt-3 text-lg leading-8 text-slate-700">{{ $message }}</p>
        <p class="mt-3 text-slate-600">Tu avance y tus datos están guardados. El resto de la plataforma funciona con normalidad.</p>
        <div class="mt-7 flex flex-wrap gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">Ir a mi panel</a>
            @else
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">Ir al inicio</a>
            @endauth
        </div>
    </div>
</div>
@endsection
