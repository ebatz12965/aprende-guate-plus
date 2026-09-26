<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Aprende Guate') }}</title>
    @auth
    <script>if (localStorage.getItem('student-navigation') === 'side') document.documentElement.classList.add('student-side-preference');</script>
    @endauth
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
@php
    $studentNavClass = fn (string $pattern) => request()->routeIs($pattern)
        ? 'student-sidebar-link student-sidebar-link-active'
        : 'student-sidebar-link';
    $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $twoFactorPending = auth()->check() && ! auth()->user()->hasTwoFactorEnabled();
@endphp
<div x-data="{
        menuOpen: false,
        profileOpen: false,
        supportOpen: false,
        sideSupportOpen: {{ request()->routeIs('adaptive.tutor', 'adaptive.mentorship') ? 'true' : 'false' }},
        sidebarOpen: false,
        showTwoFactorReminder: {{ session('two_factor_reminder') ? 'true' : 'false' }},
        isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
        navMode: 'top',
        init() {
            if (this.isAuthenticated) this.navMode = localStorage.getItem('student-navigation') || 'top';
        },
        setNavMode(mode) {
            if (!this.isAuthenticated) return;
            this.navMode = mode;
            localStorage.setItem('student-navigation', mode);
            document.documentElement.classList.toggle('student-side-preference', mode === 'side');
            this.menuOpen = false;
            this.sidebarOpen = false;
        }
    }" class="min-h-screen">
    @if (session('two_factor_reminder'))
        <div x-cloak x-show="showTwoFactorReminder" class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="two-factor-reminder-title">
            <section @click.outside="showTwoFactorReminder = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><div class="grid h-11 w-11 place-items-center rounded-xl bg-orange-100 text-xl" aria-hidden="true">🔒</div><button @click="showTwoFactorReminder = false" class="rounded-lg p-1 text-slate-500 hover:bg-slate-100" aria-label="Cerrar aviso">✕</button></div>
                <p class="mt-5 text-xs font-extrabold tracking-[0.14em] text-orange-700">RECOMENDACIÓN DE SEGURIDAD</p>
                <h2 id="two-factor-reminder-title" class="mt-2 text-xl font-extrabold text-slate-900">Activa la verificación en dos pasos</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">Añade un código temporal de tu aplicación autenticadora además de tu contraseña. Así proteges mejor tu cuenta aunque alguien conozca tu clave.</p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button @click="showTwoFactorReminder = false" class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Ahora no</button><a href="{{ route('profile.edit', ['section' => 'security']) }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-center text-sm font-extrabold text-white hover:bg-blue-800">Configurar 2FA</a></div>
            </section>
        </div>
    @endif
    <header x-show="navMode === 'top'" class="student-top-shell sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <nav class="flex h-[72px] w-full items-center justify-between px-4 sm:px-6 lg:px-8 2xl:px-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3 py-4 text-blue-950">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-700 text-white shadow-lg shadow-blue-700/20"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V5.75A1.75 1.75 0 0 1 5.75 4H20v14.5"/><path d="M4 19.5c0-1.1.9-2 2-2h14"/><path d="M8 8h8M8 12h6"/></svg></span>
                <span class="text-lg font-extrabold tracking-tight">Aprende Guate</span>
            </a>
            <div class="hidden items-center gap-1 md:flex">
                @auth
                    @role('Admin')
                        <a href="{{ route('admin.dashboard') }}" class="platform-nav-link">Panel</a><a href="{{ route('adaptive.dashboard') }}" class="platform-nav-link">Experiencia estudiante</a><a href="{{ route('admin.users.index') }}" class="platform-nav-link">Usuarios</a><a href="{{ route('admin.ai-settings.edit') }}" class="platform-nav-link">Límites IA</a><a href="{{ route('admin.mail-settings.edit') }}" class="platform-nav-link">Correo</a>
                    @else
                        @if(auth()->user()->hasRole('Instructor'))
                            <a href="{{ route('mentor.dashboard') }}" class="platform-nav-link">Panel mentor</a><a href="{{ route('mentor.calendar') }}" class="platform-nav-link">Calendario</a><a href="{{ route('mentor.sessions') }}" class="platform-nav-link">Sesiones y chats</a><a href="{{ route('mentor.settings') }}" class="platform-nav-link">Perfil de mentor</a>
                        @else
                            <a href="{{ route('adaptive.dashboard') }}" class="platform-nav-link">Mi panel</a><a href="{{ route('adaptive.path') }}" class="platform-nav-link">Mi ruta</a><div class="relative" @mouseenter="supportOpen = true" @mouseleave="supportOpen = false"><button @click="supportOpen = !supportOpen" class="platform-nav-link inline-flex items-center gap-1" aria-haspopup="true" :aria-expanded="supportOpen">Aprendizaje y apoyo <span class="text-xs">⌄</span></button><div x-cloak x-show="supportOpen" x-transition @click.outside="supportOpen = false" class="absolute left-0 top-full z-40 mt-1 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl"><a href="{{ route('adaptive.tutor') }}" class="platform-menu-link">Tutor IA</a><a href="{{ route('adaptive.mentorship') }}" class="platform-menu-link">Mentoría profesional</a></div></div><a href="{{ route('adaptive.challenges') }}" class="platform-nav-link">Retos</a><a href="{{ route('adaptive.career') }}" class="platform-nav-link">Perfil</a>
                        @endif
                    @endrole
                @else @endauth
            </div>
            <div class="hidden items-center gap-3 md:flex">
                @auth
                    <div class="relative"><button @click="profileOpen = !profileOpen" class="flex items-center gap-2 rounded-xl p-1.5 text-sm font-semibold text-slate-700 transition hover:bg-blue-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-100 text-blue-800">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>@if($twoFactorPending)<span class="h-2.5 w-2.5 rounded-full bg-orange-500 ring-2 ring-white" title="Verificación en dos pasos pendiente"></span>@endif<span class="max-w-28 truncate">{{ auth()->user()->name }}</span></button><div x-cloak x-show="profileOpen" @click.outside="profileOpen = false" x-transition class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl"><a href="{{ route('notifications.index') }}" class="platform-menu-link">Notificaciones @if($twoFactorPending)<span class="float-right mt-1 h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>@endif @if($unreadNotifications)<span class="float-right rounded-full bg-blue-100 px-2 text-xs text-blue-700">{{ $unreadNotifications }}</span>@endif</a><a href="{{ route('adaptive.career') }}" class="platform-menu-link">Perfil profesional</a><a href="{{ route('profile.edit') }}" class="platform-menu-link">Configuración</a><button @click="setNavMode('side')" class="platform-menu-link w-full text-left">Usar menú lateral</button><form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-menu-link w-full text-left">Cerrar sesión</button></form></div></div>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">Iniciar sesión</a>
                    @if (Route::has('register'))<a href="{{ route('register') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-700/20 transition hover:bg-blue-800">Crear cuenta</a>@endif
                @endauth
            </div>
            <button @click="menuOpen = !menuOpen" class="rounded-lg p-2 text-slate-600 hover:bg-blue-50 md:hidden" aria-label="Abrir menú"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        </nav>
        <div x-cloak x-show="menuOpen" x-transition class="border-t border-slate-100 bg-white px-4 py-3 md:hidden"><div class="flex flex-col gap-1">@auth @role('Admin')<a href="{{ route('admin.dashboard') }}" class="platform-mobile-link">Panel administrativo</a><a href="{{ route('adaptive.dashboard') }}" class="platform-mobile-link">Experiencia estudiante</a><a href="{{ route('admin.users.index') }}" class="platform-mobile-link">Usuarios</a><a href="{{ route('admin.ai-settings.edit') }}" class="platform-mobile-link">Límites IA</a><a href="{{ route('admin.mail-settings.edit') }}" class="platform-mobile-link">Correo saliente</a>@else @if(auth()->user()->hasRole('Instructor'))<a href="{{ route('mentor.dashboard') }}" class="platform-mobile-link">Panel mentor · Solicitudes</a><a href="{{ route('mentor.calendar') }}" class="platform-mobile-link">Calendario</a><a href="{{ route('mentor.sessions') }}" class="platform-mobile-link">Sesiones y chats</a><a href="{{ route('mentor.settings') }}" class="platform-mobile-link">Perfil de mentor</a>@else<a href="{{ route('adaptive.dashboard') }}" class="platform-mobile-link">Mi panel</a><a href="{{ route('adaptive.path') }}" class="platform-mobile-link">Mi ruta</a><button @click="supportOpen = !supportOpen" class="platform-mobile-link flex w-full items-center justify-between text-left">Aprendizaje y apoyo <span>⌄</span></button><div x-show="supportOpen" x-cloak class="ml-3 border-l border-blue-100 pl-3"><a href="{{ route('adaptive.tutor') }}" class="platform-mobile-link">Tutor IA</a><a href="{{ route('adaptive.mentorship') }}" class="platform-mobile-link">Mentoría profesional</a></div><a href="{{ route('adaptive.challenges') }}" class="platform-mobile-link">Retos</a><a href="{{ route('adaptive.career') }}" class="platform-mobile-link">Perfil profesional</a>@endif @endrole<a href="{{ route('profile.edit') }}" class="platform-mobile-link">Configuración</a><button @click="setNavMode('side')" class="platform-mobile-link w-full text-left">Usar menú lateral</button><form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-mobile-link w-full text-left">Cerrar sesión</button></form>@else<a href="{{ route('login') }}" class="platform-mobile-link">Iniciar sesión</a>@endauth</div></div>
    </header>

    <div x-cloak x-show="navMode === 'side' && sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"></div>
    <aside x-cloak x-show="navMode === 'side'" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="student-side-shell fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-blue-100 bg-white transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6"><a href="{{ route('home') }}" class="flex items-center gap-3 text-blue-950"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-700 text-white shadow-lg shadow-blue-700/20"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V5.75A1.75 1.75 0 0 1 5.75 4H20v14.5"/><path d="M4 19.5c0-1.1.9-2 2-2h14"/><path d="M8 8h8M8 12h6"/></svg></span><span><span class="block text-base font-extrabold tracking-tight">Aprende Guate</span><span class="block text-xs font-medium text-blue-700">Mi aprendizaje</span></span></a><button @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-blue-50 lg:hidden" aria-label="Cerrar menú">×</button></div>
        <nav class="flex-1 overflow-y-auto px-4 py-6">
            @auth
                @role('Admin')
                    <p class="student-sidebar-label">ADMINISTRACIÓN</p><a href="{{ route('admin.dashboard') }}" class="{{ $studentNavClass('admin.dashboard') }}">Panel administrativo</a><a href="{{ route('adaptive.dashboard') }}" class="{{ $studentNavClass('adaptive.*') }}">Experiencia estudiante</a><a href="{{ route('admin.users.index') }}" class="{{ $studentNavClass('admin.users.*') }}">Usuarios</a><a href="{{ route('admin.ai-settings.edit') }}" class="{{ $studentNavClass('admin.ai-settings.*') }}">Límites de IA</a><a href="{{ route('admin.mail-settings.edit') }}" class="{{ $studentNavClass('admin.mail-settings.*') }}">Correo saliente</a>
                @else
                    @if(auth()->user()->hasRole('Instructor'))
                        <p class="student-sidebar-label">PANEL DE INSTRUCTOR</p><a href="{{ route('mentor.dashboard') }}" class="{{ $studentNavClass('mentor.dashboard') }}">Solicitudes</a><a href="{{ route('mentor.calendar') }}" class="{{ $studentNavClass('mentor.calendar') }}">Calendario</a><a href="{{ route('mentor.sessions') }}" class="{{ $studentNavClass('mentor.sessions') }}">Sesiones y chats</a><a href="{{ route('mentor.settings') }}" class="{{ $studentNavClass('mentor.settings') }}">Perfil de mentor</a>
                    @else
                        <p class="student-sidebar-label">MI APRENDIZAJE</p><a href="{{ route('adaptive.dashboard') }}" class="{{ $studentNavClass('adaptive.dashboard') }}">Mi panel</a><a href="{{ route('adaptive.path') }}" class="{{ $studentNavClass('adaptive.path*') }}">Mi ruta</a><button @click="sideSupportOpen = !sideSupportOpen" class="student-sidebar-link flex w-full items-center justify-between text-left">Aprendizaje y apoyo <span class="text-xs" :class="sideSupportOpen ? 'rotate-180' : ''">⌄</span></button><div x-show="sideSupportOpen" x-cloak class="ml-3 border-l border-blue-100 pl-2"><a href="{{ route('adaptive.tutor') }}" class="{{ $studentNavClass('adaptive.tutor') }}">Tutor IA</a><a href="{{ route('adaptive.mentorship') }}" class="{{ $studentNavClass('adaptive.mentorship') }}">Mentoría profesional</a></div><a href="{{ route('adaptive.challenges') }}" class="{{ $studentNavClass('adaptive.challenges') }}">Retos</a><a href="{{ route('adaptive.career') }}" class="{{ $studentNavClass('adaptive.career') }}">Perfil profesional</a>
                    @endif
                @endrole
            @else
                <p class="student-sidebar-label">APRENDE GUATE</p><a href="{{ route('login') }}" class="{{ $studentNavClass('login') }}">Iniciar sesión</a>
            @endauth
        </nav>
        <div class="border-t border-slate-100 p-4">@auth<a href="{{ route('notifications.index') }}" class="student-sidebar-link">Notificaciones @if($twoFactorPending)<span class="float-right mt-1 h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>@endif @if($unreadNotifications)<span class="ml-auto rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">{{ $unreadNotifications }}</span>@endif</a><a href="{{ route('profile.edit') }}" class="student-sidebar-link">Configuración</a><button @click="setNavMode('top')" class="student-layout-switch">Usar menú superior</button><form method="POST" action="{{ route('logout') }}">@csrf<button class="student-sidebar-link w-full text-left text-rose-700 hover:bg-rose-50 hover:text-rose-800">Cerrar sesión</button></form>@else<a href="{{ route('login') }}" class="student-sidebar-link">Iniciar sesión</a><a href="{{ route('register') }}" class="student-layout-switch">Crear cuenta</a>@endauth</div>
    </aside>

    <div :class="navMode === 'side' ? 'lg:pl-72' : ''" class="min-h-screen transition-[padding] duration-200">
        <header x-cloak x-show="navMode === 'side'" class="student-side-topbar sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8"><div class="flex items-center gap-3"><button @click="sidebarOpen = true" class="rounded-xl p-2 text-slate-600 hover:bg-blue-50 lg:hidden" aria-label="Abrir menú"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button><div><p class="text-sm font-semibold text-slate-900">Mi espacio de aprendizaje</p><p class="hidden text-xs text-slate-500 sm:block">Ruta, práctica y crecimiento profesional</p></div></div>@auth<div class="relative"><button @click="profileOpen = !profileOpen" class="flex items-center gap-2 rounded-xl p-1.5 text-sm font-semibold text-slate-700 hover:bg-blue-50"><span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-100 text-blue-800">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>@if($twoFactorPending)<span class="h-2.5 w-2.5 rounded-full bg-orange-500 ring-2 ring-white" title="Verificación en dos pasos pendiente"></span>@endif<span class="hidden max-w-32 truncate sm:inline">{{ auth()->user()->name }}</span></button><div x-cloak x-show="profileOpen" @click.outside="profileOpen = false" class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl"><a href="{{ route('notifications.index') }}" class="platform-menu-link">Notificaciones @if($twoFactorPending)<span class="float-right mt-1 h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>@endif @if($unreadNotifications)<span class="float-right rounded-full bg-blue-100 px-2 text-xs text-blue-700">{{ $unreadNotifications }}</span>@endif</a><a href="{{ route('adaptive.career') }}" class="platform-menu-link">Perfil profesional</a><a href="{{ route('profile.edit') }}" class="platform-menu-link">Configuración</a><button @click="setNavMode('top')" class="platform-menu-link w-full text-left">Usar menú superior</button><form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-menu-link w-full text-left">Cerrar sesión</button></form></div></div>@endauth</header>
        <main class="student-content w-full px-4 py-8 sm:px-6 lg:px-8 lg:py-10 2xl:px-12">
            @if (session('two_factor_reminder'))
                <div class="mb-6 flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-4 text-sm text-orange-900 sm:flex-row sm:items-center sm:justify-between"><div><strong>Recomendación de seguridad:</strong> la verificación en dos pasos no está activa.</div><a href="{{ route('profile.edit', ['section' => 'security']) }}" class="font-extrabold text-orange-800 underline">Configurar ahora</a></div>
            @endif            @if (session('success'))<div class="mb-6 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800"><span class="h-2 w-2 rounded-full bg-blue-500"></span>{{ session('success') }}</div>@endif
            @if (session('error'))<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
