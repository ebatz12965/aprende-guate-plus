<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Acceso | {{ config('app.name', 'Aprende Guate') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        <main class="min-h-screen lg:grid lg:grid-cols-2">
            <section class="relative hidden overflow-hidden bg-sky-950 px-12 py-12 lg:flex lg:flex-col lg:justify-between">
                <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 10% 20%, #38bdf8 0, transparent 28%), radial-gradient(circle at 85% 80%, #2dd4bf 0, transparent 24%);"></div>
                <a href="{{ route('home') }}" class="relative flex items-center gap-3 self-start text-white">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5V5.75A1.75 1.75 0 0 1 5.75 4H20v14.5"/><path d="M4 19.5c0-1.1.9-2 2-2h14"/><path d="M8 8h8M8 12h6"/></svg>
                    </span>
                    <span class="text-lg font-bold tracking-tight">Aprende Guate</span>
                </a>

                <div class="relative max-w-xl">
                    <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-sky-300/30 bg-sky-400/10 px-4 py-2 text-sm font-medium text-sky-100">
                        <span class="h-2 w-2 rounded-full bg-teal-300"></span> Educación que avanza contigo
                    </p>
                    <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white xl:text-5xl">Tu próximo aprendizaje empieza hoy.</h1>
                    <p class="mt-6 max-w-md text-lg leading-8 text-sky-100/85">Accede a tus cursos, lecciones, evaluaciones y avances desde un mismo lugar.</p>
                    <div class="mt-10 flex gap-6 border-t border-white/15 pt-7 text-sm text-sky-100">
                        <div><strong class="block text-2xl font-bold text-white">Cursos</strong> a tu ritmo</div>
                        <div><strong class="block text-2xl font-bold text-white">Progreso</strong> siempre visible</div>
                    </div>
                </div>

                <p class="relative text-sm text-sky-200/70">Plataforma educativa de Guatemala</p>
            </section>

            <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-10 flex items-center gap-3 text-sky-950 lg:hidden">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-700 text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V5.75A1.75 1.75 0 0 1 5.75 4H20v14.5"/><path d="M4 19.5c0-1.1.9-2 2-2h14"/></svg></span>
                        <span class="text-lg font-bold">Aprende Guate</span>
                    </a>
                    {{ $slot }}
                </div>
            </section>
        </main>
    </body>
</html>
