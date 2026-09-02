<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Aprende Guate') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div x-data="{ menuOpen: false, profileOpen: false }" class="min-h-screen">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <nav class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3 py-4 text-sky-950">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-700 text-white shadow-lg shadow-sky-700/20"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V5.75A1.75 1.75 0 0 1 5.75 4H20v14.5"/><path d="M4 19.5c0-1.1.9-2 2-2h14"/><path d="M8 8h8M8 12h6"/></svg></span>
                    <span class="text-lg font-extrabold tracking-tight">Aprende Guate</span>
                </a>
                <div class="hidden items-center gap-1 md:flex">
                    @auth
                        @role('Admin')
                            <a href="{{ route('admin.dashboard') }}" class="platform-nav-link">Panel</a><a href="{{ route('adaptive.dashboard') }}" class="platform-nav-link">Experiencia estudiante</a><a href="{{ route('admin.users.index') }}" class="platform-nav-link">Usuarios</a><a href="{{ route('admin.courses.index') }}" class="platform-nav-link">Recursos</a>
                        @else
                            @if(auth()->user()->hasRole('Instructor'))
                                <a href="{{ route('mentor.dashboard') }}" class="platform-nav-link">Panel mentor</a><a href="{{ route('admin.courses.index') }}" class="platform-nav-link">Recursos</a>
                            @else
                                <a href="{{ route('adaptive.dashboard') }}" class="platform-nav-link">Mi panel</a><a href="{{ route('adaptive.path') }}" class="platform-nav-link">Mi ruta</a><a href="{{ route('adaptive.tutor') }}" class="platform-nav-link">Tutor IA</a><a href="{{ route('adaptive.challenges') }}" class="platform-nav-link">Retos</a><a href="{{ route('adaptive.career') }}" class="platform-nav-link">Perfil</a>
                            @endif
                        @endrole
                    @else
                        <a href="{{ route('public.courses') }}" class="platform-nav-link">Cursos</a><a href="{{ route('public.instructors') }}" class="platform-nav-link">Instructores</a>
                    @endauth
                </div>
                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <div class="relative"><button @click="profileOpen = !profileOpen" class="flex items-center gap-2 rounded-xl p-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"><span class="grid h-8 w-8 place-items-center rounded-lg bg-sky-100 text-sky-800">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span><span class="max-w-28 truncate">{{ auth()->user()->name }}</span></button><div x-cloak x-show="profileOpen" @click.outside="profileOpen = false" x-transition class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl"><a href="{{ route('adaptive.career') }}" class="platform-menu-link">Perfil profesional</a><a href="{{ route('profile.edit') }}" class="platform-menu-link">Cuenta y seguridad</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-menu-link w-full text-left">Cerrar sesión</button></form></div></div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-bold text-sky-800 hover:text-sky-950">Iniciar sesión</a>
                        @if (Route::has('register'))<a href="{{ route('register') }}" class="rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-sky-700/20 transition hover:bg-sky-800">Crear cuenta</a>@endif
                    @endauth
                </div>
                <button @click="menuOpen = !menuOpen" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Abrir menú"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            </nav>
            <div x-cloak x-show="menuOpen" x-transition class="border-t border-slate-100 bg-white px-4 py-3 md:hidden"><div class="flex flex-col gap-1">@auth @role('Admin')<a href="{{ route('admin.dashboard') }}" class="platform-mobile-link">Panel administrativo</a><a href="{{ route('adaptive.dashboard') }}" class="platform-mobile-link">Experiencia estudiante</a><a href="{{ route('admin.users.index') }}" class="platform-mobile-link">Usuarios</a>@else @if(auth()->user()->hasRole('Instructor'))<a href="{{ route('mentor.dashboard') }}" class="platform-mobile-link">Panel mentor</a><a href="{{ route('admin.courses.index') }}" class="platform-mobile-link">Recursos</a>@else<a href="{{ route('adaptive.dashboard') }}" class="platform-mobile-link">Mi panel</a><a href="{{ route('adaptive.path') }}" class="platform-mobile-link">Mi ruta</a><a href="{{ route('adaptive.tutor') }}" class="platform-mobile-link">Tutor IA</a><a href="{{ route('adaptive.challenges') }}" class="platform-mobile-link">Retos</a><a href="{{ route('adaptive.career') }}" class="platform-mobile-link">Perfil profesional</a>@endif @endrole<a href="{{ route('profile.edit') }}" class="platform-mobile-link">Mi perfil</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-mobile-link w-full text-left">Cerrar sesión</button></form>@else<a href="{{ route('login') }}" class="platform-mobile-link">Iniciar sesión</a>@endauth</div></div>
        </header>
        <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            @if (session('success'))<div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ session('success') }}</div>@endif
            @if (session('error'))<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</body>
</html>
