<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso | Aprende Guate</title>
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
    <main class="min-h-screen lg:grid lg:grid-cols-[minmax(0,1.08fr)_minmax(420px,.92fr)]">
        <section class="relative hidden overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-blue-800 px-12 py-12 lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="absolute inset-0 opacity-40" style="background-image: radial-gradient(circle at 88% 18%, #64B5F6 0, transparent 25%), radial-gradient(circle at 70% 88%, #42A5F5 0, transparent 23%);"></div>
            <a href="{{ route('home') }}" class="relative flex items-center gap-3 self-start text-white">
                <img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="h-11 w-11 rounded-xl ring-1 ring-white/25">
                <span class="text-lg font-extrabold tracking-tight">Aprende Guate</span>
            </a>

            <div class="relative max-w-xl">
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white xl:text-5xl">Tu próximo paso profesional empieza hoy.</h1>
                <p class="mt-6 max-w-md text-lg leading-8 text-blue-100/90">Define tu objetivo, avanza con una ruta personalizada y demuestra tus habilidades con práctica real.</p>
                <div class="mt-10 grid max-w-lg grid-cols-3 gap-3 border-t border-white/20 pt-7 text-sm text-blue-100">
                    <div><strong class="block text-xl font-extrabold text-white">Ruta</strong> a tu medida</div>
                    <div><strong class="block text-xl font-extrabold text-white">Práctica</strong> aplicada</div>
                    <div><strong class="block text-xl font-extrabold text-white">Progreso</strong> visible</div>
                </div>
            </div>

            <p class="relative text-sm text-blue-100">Plataforma educativa de Guatemala</p>
        </section>

        <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:bg-white">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-9 lg:shadow-xl lg:shadow-blue-950/5">
                <a href="{{ route('home') }}" class="mb-9 flex items-center gap-3 text-blue-950 lg:hidden"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="h-10 w-10 rounded-xl shadow-md shadow-blue-700/20"><span class="text-lg font-extrabold">Aprende Guate</span></a>
                {{ $slot }}
            </div>
        </section>
    </main>
    @include('components.platform-footer')
</body>
</html>
