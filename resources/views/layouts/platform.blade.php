<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Aprende Guate</title>
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    @auth
        <script>
            if (localStorage.getItem('student-navigation') === 'side') document.documentElement.classList.add('student-side-preference');
            if (localStorage.getItem('platform-theme') === 'dark') document.documentElement.classList.add('dark');
        </script>
    @endauth
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    @php
        $studentNavClass = fn(string $pattern) => request()->routeIs($pattern) ? 'student-sidebar-link student-sidebar-link-active' : 'student-sidebar-link';
        $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
        $twoFactorPending = auth()->check() && !auth()->user()->hasTwoFactorEnabled();
        $activeSpace = auth()->check()
            ? session('active_space', auth()->user()->hasRole('Admin') ? 'admin' : (auth()->user()->hasRole('Instructor') ? 'mentor' : 'learning'))
            : 'learning';
        $guideTours = auth()->check() ? app(\App\Services\PlatformGuideService::class)->tours(auth()->user()->hasRole('Instructor')) : [];
    @endphp
    <div x-data="{
        menuOpen: false,
        profileOpen: false,
        supportOpen: false,
        sideSupportOpen: {{ request()->routeIs('adaptive.tutor', 'adaptive.mentorship') ? 'true' : 'false' }},
        sidebarOpen: false,
        showTwoFactorReminder: {{ session('two_factor_reminder') ? 'true' : 'false' }},
        isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
        darkMode: localStorage.getItem('platform-theme') === 'dark',
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
        },
        toggleTheme() {
            this.darkMode = !this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('platform-theme', this.darkMode ? 'dark' : 'light');
        }
    }" class="min-h-screen">
        @if (session('two_factor_reminder'))
            <div x-cloak x-show="showTwoFactorReminder" x-transition.opacity.duration.200ms
                class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true"
                aria-labelledby="two-factor-reminder-title">
                <section @click.outside="showTwoFactorReminder = false" x-transition.scale.origin.center.duration.200ms
                    class="ag-modal-enter w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div class="grid h-11 w-11 place-items-center rounded-xl bg-orange-100 text-xl" aria-hidden="true">🔒</div><button
                            @click="showTwoFactorReminder = false" class="rounded-lg p-1 text-slate-500 hover:bg-slate-100" aria-label="Cerrar aviso">✕</button>
                    </div>
                    <h2 id="two-factor-reminder-title" class="mt-5 text-xl font-extrabold text-slate-900">Activa la verificación en dos pasos</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Añade un código temporal de tu aplicación autenticadora además de tu contraseña. Así
                        proteges mejor tu cuenta aunque alguien conozca tu clave.</p>
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button @click="showTwoFactorReminder = false"
                            class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Ahora no</button><a
                            href="{{ route('profile.edit', ['section' => 'security']) }}"
                            class="rounded-xl bg-blue-700 px-4 py-2.5 text-center text-sm font-extrabold text-white hover:bg-blue-800">Configurar 2FA</a></div>
                </section>
            </div>
        @endif
        <header x-show="navMode === 'top'" class="student-top-shell sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <nav class="flex h-[72px] w-full items-center justify-between px-4 sm:px-6 lg:px-8 2xl:px-12">
                <a href="{{ route('home') }}" class="flex items-center gap-3 py-4 text-blue-950">
                    <img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="ag-brand-icon">
                    <span class="text-lg font-extrabold tracking-tight">Aprende Guate</span>
                </a>
                <div class="hidden items-center gap-1 md:flex">
                    @auth
                        @if ($activeSpace === 'admin' && auth()->user()->hasRole('Admin'))
                            <a href="{{ route('admin.dashboard') }}" class="platform-nav-link">Panel</a><a href="{{ route('adaptive.dashboard') }}"
                                class="platform-nav-link">Experiencia estudiante</a><a href="{{ route('admin.users.index') }}"
                                class="platform-nav-link">Usuarios</a><a href="{{ route('admin.ai-settings.edit') }}" class="platform-nav-link">Límites IA</a><a
                                href="{{ route('admin.mail-settings.edit') }}" class="platform-nav-link">Correo</a>
                        @elseif($activeSpace === 'mentor' && (auth()->user()->hasRole('Instructor') || auth()->user()->hasRole('Admin')))
                            <a href="{{ route('mentor.dashboard') }}" class="platform-nav-link">Panel mentor</a><a href="{{ route('mentor.calendar') }}"
                                class="platform-nav-link">Calendario</a><a href="{{ route('mentor.sessions') }}" class="platform-nav-link">Sesiones y chats</a><a
                                href="{{ route('mentor.settings') }}" class="platform-nav-link">Perfil de mentor</a>
                        @else
                            <a href="{{ route('adaptive.dashboard') }}" data-tour="mi-panel" class="platform-nav-link">Mi panel</a><a
                                href="{{ route('adaptive.path') }}" data-tour="mi-ruta" class="platform-nav-link">Mi ruta</a>
                            <div class="relative" @mouseenter="supportOpen = true" @mouseleave="supportOpen = false"><button data-tour="apoyo"
                                    @click="supportOpen = !supportOpen" class="platform-nav-link inline-flex items-center gap-1" aria-haspopup="true"
                                    :aria-expanded="supportOpen">Aprendizaje y apoyo <span class="text-xs">⌄</span></button>
                                <div x-cloak x-show="supportOpen" x-transition @click.outside="supportOpen = false"
                                    class="absolute left-0 top-full z-40 mt-1 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl"><a
                                        href="{{ route('adaptive.tutor') }}" class="platform-menu-link">Tutor IA</a><a href="{{ route('adaptive.mentorship') }}"
                                        class="platform-menu-link">Mentoría profesional</a><a href="{{ route('mentor-application.create') }}"
                                        class="platform-menu-link">Solicitar ser mentor</a></div>
                            </div><a href="{{ route('adaptive.challenges') }}" data-tour="retos" class="platform-nav-link">Retos</a><a
                                href="{{ route('adaptive.career') }}" data-tour="perfil" class="platform-nav-link">Perfil</a>
                        @endif
                    @else
                    @endauth
                </div>
                <div class="hidden items-center gap-3 md:flex">
                    @if (auth()->check() && (auth()->user()->hasRole('Instructor') || auth()->user()->hasRole('Admin')))
                        <div class="relative" x-data="{ spacesOpen: false }"><button @click="spacesOpen = !spacesOpen" @click.outside="spacesOpen = false"
                                class="platform-nav-link inline-flex items-center gap-2" aria-label="Cambiar espacio"><span
                                    class="grid h-7 w-7 place-items-center rounded-lg bg-blue-100 text-blue-700">⇄</span><span
                                    class="hidden lg:inline">{{ $activeSpace === 'admin' ? 'Administración' : ($activeSpace === 'mentor' ? 'Espacio mentor' : 'Mi aprendizaje') }}</span></button>
                            <div x-cloak x-show="spacesOpen" x-transition
                                class="absolute right-0 top-full z-40 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                @if ($activeSpace !== 'learning')
                                    <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space"
                                            value="learning"><button class="platform-menu-link w-full text-left">Mi aprendizaje</button></form>
                                    @endif @if ($activeSpace !== 'mentor')
                                        <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space"
                                                value="mentor"><button class="platform-menu-link w-full text-left">Espacio mentor</button></form>
                                        @endif @if (auth()->user()->hasRole('Admin') && $activeSpace !== 'admin')
                                            <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space"
                                                    value="admin"><button class="platform-menu-link w-full text-left">Administración</button></form>
                                        @endif
                            </div>
                        </div>
                    @endif
                    @auth
                        <div class="relative"><button data-tour="cuenta" @click="profileOpen = !profileOpen"
                                class="flex items-center gap-2 rounded-xl p-1.5 text-sm font-semibold text-slate-700 transition hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800"><span
                                    class="grid h-8 w-8 place-items-center rounded-lg bg-blue-100 text-blue-800">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>
                                @if ($twoFactorPending)
                                    <span class="h-2.5 w-2.5 rounded-full bg-orange-500 ring-2 ring-white" title="Verificación en dos pasos pendiente"></span>
                                @endif
                                <span class="max-w-28 truncate">
                                    {{ auth()->user()->name }}</span>
                            </button>
                            <div x-cloak x-show="profileOpen" @click.outside="profileOpen = false" x-transition
                                class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                <a href="{{ route('notifications.index') }}" class="platform-menu-link">Notificaciones @if ($twoFactorPending)
                                        <span class="float-right mt-1 h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>
                                        @endif @if ($unreadNotifications)
                                            <span class="float-right rounded-full bg-blue-100 px-2 text-xs text-blue-700">{{ $unreadNotifications }}</span>
                                        @endif
                                </a>
                                <a href="{{ route('adaptive.career') }}" class="platform-menu-link">Perfil profesional</a><a href="{{ route('profile.edit') }}"
                                    class="platform-menu-link">Configuración</a><button @click="toggleTheme()"
                                    class="platform-menu-link flex w-full items-center justify-between text-left"><span
                                        x-text="darkMode ? 'Usar tema claro' : 'Usar tema oscuro'"></span><span aria-hidden="true"
                                        x-text="darkMode ? '☀' : '☾'"></span></button><button @click="setNavMode('side')"
                                    class="platform-menu-link w-full text-left">Usar menú lateral</button>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-menu-link w-full text-left">Cerrar
                                        sesión</button></form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-bold text-blue-800 hover:text-blue-950">Iniciar sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-700/20 transition hover:bg-blue-800">Crear
                                cuenta</a>
                        @endif
                    @endauth
                </div>
                <button @click="menuOpen = !menuOpen" class="rounded-lg p-2.5 text-slate-600 hover:bg-blue-50 md:hidden" aria-label="Abrir menú"><svg
                        class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg></button>
            </nav>
            <div x-cloak x-show="menuOpen" x-transition
                class="border-t border-slate-100 bg-white px-4 py-3 md:hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-1">@auth @if ($activeSpace === 'admin' && auth()->user()->hasRole('Admin'))
                        <a href="{{ route('admin.dashboard') }}" class="platform-mobile-link">Administración</a><a href="{{ route('adaptive.dashboard') }}"
                            class="platform-mobile-link">Mi aprendizaje</a>
                    @elseif($activeSpace === 'mentor' && (auth()->user()->hasRole('Instructor') || auth()->user()->hasRole('Admin')))
                        <a href="{{ route('mentor.dashboard') }}" class="platform-mobile-link">Panel mentor · Solicitudes</a><a
                            href="{{ route('mentor.calendar') }}" class="platform-mobile-link">Calendario</a><a href="{{ route('mentor.sessions') }}"
                            class="platform-mobile-link">Sesiones y chats</a><a href="{{ route('mentor.settings') }}" class="platform-mobile-link">Perfil de
                        mentor</a>@else<a href="{{ route('adaptive.dashboard') }}" class="platform-mobile-link">Mi panel</a><a
                            href="{{ route('adaptive.path') }}" class="platform-mobile-link">Mi ruta</a><button @click="supportOpen = !supportOpen"
                            class="platform-mobile-link flex w-full items-center justify-between text-left">Aprendizaje y apoyo <span>⌄</span></button>
                        <div x-show="supportOpen" x-cloak class="ml-3 border-l border-blue-100 pl-3"><a href="{{ route('adaptive.tutor') }}"
                                class="platform-mobile-link">Tutor IA</a><a href="{{ route('adaptive.mentorship') }}" class="platform-mobile-link">Mentoría
                                profesional</a></div><a href="{{ route('adaptive.challenges') }}" class="platform-mobile-link">Retos</a><a
                            href="{{ route('adaptive.career') }}" class="platform-mobile-link">Perfil profesional</a>
                    @endif
                    <a href="{{ route('workspace.switch') }}" class="hidden">
                    </a>
                    <div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>
                    <p class="px-3 pt-1 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cambiar espacio</p>
                    @if ($activeSpace !== 'learning')
                        <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space" value="learning"><button
                                class="platform-mobile-link w-full text-left">Mi aprendizaje</button></form>
                        @endif @if (auth()->user()->hasRole('Instructor') || auth()->user()->hasRole('Admin'))
                            @if ($activeSpace !== 'mentor')
                                <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space"
                                        value="mentor"><button class="platform-mobile-link w-full text-left">Espacio mentor</button></form>
                            @endif
                            @endif @if (auth()->user()->hasRole('Admin') && $activeSpace !== 'admin')
                                <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space"
                                        value="admin"><button class="platform-mobile-link w-full text-left">Administración</button></form>
                            @endif
                            <a href="{{ route('profile.edit') }}" class="platform-mobile-link">
                                Configuración</a><button @click="toggleTheme()"
                                class="platform-mobile-link flex w-full items-center justify-between text-left"><span
                                    x-text="darkMode ? 'Usar tema claro' : 'Usar tema oscuro'"></span><span aria-hidden="true"
                                    x-text="darkMode ? '☀' : '☾'"></span></button><button @click="setNavMode('side')"
                                class="platform-mobile-link w-full text-left">Usar menú lateral</button>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-mobile-link w-full text-left">Cerrar
                            sesión</button></form>@else<a href="{{ route('login') }}" class="platform-mobile-link">Iniciar sesión</a>@endauth
        </div>
    </div>
</header>

<div x-cloak x-show="navMode === 'side' && sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"></div>
<aside x-cloak x-show="navMode === 'side'" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="student-side-shell fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-blue-100 bg-white transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6"><a href="{{ route('home') }}"
            class="flex items-center gap-3 text-blue-950"><img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt=""
                class="ag-brand-icon"><span><span class="block text-base font-extrabold tracking-tight">Aprende Guate</span><span
                    class="block text-xs font-medium text-blue-700">Mi aprendizaje</span></span></a><button @click="sidebarOpen = false"
            class="rounded-lg p-2.5 text-slate-600 hover:bg-blue-50 lg:hidden" aria-label="Cerrar menú">×</button></div>
    <nav class="flex-1 overflow-y-auto px-4 py-6">
        @auth
            @if ($activeSpace === 'admin' && auth()->user()->hasRole('Admin'))
                <p class="student-sidebar-label">ADMINISTRACIÓN</p><a href="{{ route('admin.dashboard') }}"
                    class="{{ $studentNavClass('admin.dashboard') }}">Panel administrativo</a><a href="{{ route('adaptive.dashboard') }}"
                    class="{{ $studentNavClass('adaptive.*') }}">Experiencia estudiante</a><a href="{{ route('admin.users.index') }}"
                    class="{{ $studentNavClass('admin.users.*') }}">Usuarios</a><a href="{{ route('admin.ai-settings.edit') }}"
                    class="{{ $studentNavClass('admin.ai-settings.*') }}">Límites de IA</a><a href="{{ route('admin.mail-settings.edit') }}"
                    class="{{ $studentNavClass('admin.mail-settings.*') }}">Correo saliente</a>
            @elseif($activeSpace === 'mentor' && (auth()->user()->hasRole('Instructor') || auth()->user()->hasRole('Admin')))
                <p class="student-sidebar-label">PANEL DE INSTRUCTOR</p><a href="{{ route('mentor.dashboard') }}"
                    class="{{ $studentNavClass('mentor.dashboard') }}">Solicitudes</a><a href="{{ route('mentor.calendar') }}"
                    class="{{ $studentNavClass('mentor.calendar') }}">Calendario</a><a href="{{ route('mentor.sessions') }}"
                    class="{{ $studentNavClass('mentor.sessions') }}">Sesiones y chats</a><a href="{{ route('mentor.settings') }}"
                    class="{{ $studentNavClass('mentor.settings') }}">Perfil de mentor</a>
            @else
                <p class="student-sidebar-label">MI APRENDIZAJE</p><a href="{{ route('adaptive.dashboard') }}"
                    class="{{ $studentNavClass('adaptive.dashboard') }}">Mi panel</a><a href="{{ route('adaptive.path') }}"
                    class="{{ $studentNavClass('adaptive.path*') }}">Mi ruta</a><button @click="sideSupportOpen = !sideSupportOpen"
                    class="student-sidebar-link flex w-full items-center justify-between text-left">Aprendizaje y apoyo <span class="text-xs"
                        :class="sideSupportOpen ? 'rotate-180' : ''">⌄</span></button>
                <div x-show="sideSupportOpen" x-cloak class="ml-3 border-l border-blue-100 pl-2"><a href="{{ route('adaptive.tutor') }}"
                        class="{{ $studentNavClass('adaptive.tutor') }}">Tutor IA</a><a href="{{ route('adaptive.mentorship') }}"
                        class="{{ $studentNavClass('adaptive.mentorship') }}">Mentoría profesional</a><a href="{{ route('mentor-application.create') }}"
                        class="{{ $studentNavClass('mentor-application.*') }}">Solicitar ser mentor</a></div><a
                    href="{{ route('adaptive.challenges') }}" class="{{ $studentNavClass('adaptive.challenges') }}">Retos</a><a
                    href="{{ route('adaptive.career') }}" class="{{ $studentNavClass('adaptive.career') }}">Perfil profesional</a>
            @endif
        @else
            <p class="student-sidebar-label">APRENDE GUATE</p><a href="{{ route('login') }}" class="{{ $studentNavClass('login') }}">Iniciar sesión</a>
        @endauth
    </nav>
    <div class="border-t border-slate-100 p-4 dark:border-slate-800">@auth<a href="{{ route('notifications.index') }}"
                class="student-sidebar-link">Notificaciones @if ($twoFactorPending)
                    <span class="float-right mt-1 h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>
                    @endif @if ($unreadNotifications)
                        <span class="ml-auto rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">{{ $unreadNotifications }}</span>
                    @endif
            </a>
            <a href="{{ route('profile.edit') }}" class="student-sidebar-link">Configuración</a><button @click="toggleTheme()"
                class="student-layout-switch flex w-full items-center justify-between"><span
                    x-text="darkMode ? 'Usar tema claro' : 'Usar tema oscuro'"></span><span aria-hidden="true"
                    x-text="darkMode ? '☀' : '☾'"></span></button><button @click="setNavMode('top')" class="student-layout-switch">Usar menú
                superior</button>
            <form method="POST" action="{{ route('logout') }}">@csrf<button
                class="student-sidebar-link w-full text-left text-rose-700 hover:bg-rose-50 hover:text-rose-800">Cerrar sesión</button></form>@else<a
                href="{{ route('login') }}" class="student-sidebar-link">Iniciar sesión</a><a href="{{ route('register') }}"
            class="student-layout-switch">Crear cuenta</a>@endauth
    </div>
</aside>

<div :class="navMode === 'side' ? 'lg:pl-72' : ''" class="min-h-screen">
    <header x-cloak x-show="navMode === 'side'"
        class="student-side-topbar sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
        <div class="flex items-center gap-3"><button @click="sidebarOpen = true" class="rounded-xl p-2.5 text-slate-600 hover:bg-blue-50 lg:hidden"
                aria-label="Abrir menú"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg></button>
            <div>
                <p class="text-sm font-semibold text-slate-900">Mi espacio de aprendizaje</p>
                <p class="hidden text-xs text-slate-500 sm:block">Ruta, práctica y crecimiento profesional</p>
            </div>
        </div>@auth<div class="relative"><button data-tour="cuenta" @click="profileOpen = !profileOpen"
                    class="flex items-center gap-2 rounded-xl p-1.5 text-sm font-semibold text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800"><span
                        class="grid h-9 w-9 place-items-center rounded-lg bg-blue-100 text-blue-800">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>
                    @if ($twoFactorPending)
                        <span class="h-2.5 w-2.5 rounded-full bg-orange-500 ring-2 ring-white" title="Verificación en dos pasos pendiente"></span>
                    @endif
                    <span class="hidden max-w-32 truncate sm:inline">
                        {{ auth()->user()->name }}</span>
                </button>
                <div x-cloak x-show="profileOpen" @click.outside="profileOpen = false"
                    class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                    <a href="{{ route('notifications.index') }}" class="platform-menu-link">Notificaciones @if ($twoFactorPending)
                            <span class="float-right mt-1 h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>
                            @endif @if ($unreadNotifications)
                                <span class="float-right rounded-full bg-blue-100 px-2 text-xs text-blue-700">{{ $unreadNotifications }}</span>
                            @endif
                    </a>
                    <a href="{{ route('adaptive.career') }}" class="platform-menu-link">Perfil profesional</a><a href="{{ route('profile.edit') }}"
                        class="platform-menu-link">Configuración</a><button @click="toggleTheme()"
                        class="platform-menu-link flex w-full items-center justify-between text-left"><span
                            x-text="darkMode ? 'Usar tema claro' : 'Usar tema oscuro'"></span><span aria-hidden="true"
                            x-text="darkMode ? '☀' : '☾'"></span></button><button @click="setNavMode('top')"
                        class="platform-menu-link w-full text-left">Usar menú superior</button>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="platform-menu-link w-full text-left">Cerrar sesión</button>
                    </form>
                </div>
        </div>@endauth
    </header>
    <main class="student-content w-full px-4 py-8 sm:px-6 lg:px-8 lg:py-10 2xl:px-12">
        @if (session('two_factor_reminder'))
            <div
                class="mb-6 flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-4 text-sm text-orange-900 sm:flex-row sm:items-center sm:justify-between">
                <div><strong>Recomendación de seguridad:</strong> la verificación en dos pasos no está activa.</div><a
                    href="{{ route('profile.edit', ['section' => 'security']) }}" class="font-extrabold text-orange-800 underline">Configurar
                    ahora</a>
            </div>
            @endif @if (session('success'))
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800">
                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>{{ session('success') }}</div>
            @endif
            @if (session('info'))
                <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-50" role="status">{{ session('info') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
    </main>
    @include('components.platform-footer')
</div>
</div>
@if (false)
<div x-data="platformGuide()" x-init="init()" class="fixed z-[55]" style="right: 1rem; bottom: 5rem; width: min(22rem, calc(100vw - 2rem));"
    aria-live="polite">
    <section x-cloak x-show="open" x-transition.origin.bottom.right class="overflow-hidden rounded-2xl border border-sky-200 bg-white shadow-2xl">
        <header class="flex items-center gap-3 bg-sky-700 px-4 py-3 text-white">
            <img src="{{ asset('images/quetzal-guia.jpg') }}" alt="Quetzi, guía de Aprende Guate"
                class="h-10 w-10 rounded-full border-2 border-white/70 bg-white object-cover object-top">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-extrabold">Quetzi, tu guía</p>
                <p class="text-xs text-sky-50">Ayuda para usar la plataforma</p>
            </div>
            <button type="button" @click="close()" class="rounded-lg p-1.5 text-sky-50 hover:bg-sky-800 hover:text-white"
                aria-label="Cerrar guía">×</button>
        </header>
        <div class="space-y-3 bg-sky-50/60 p-4">
            <div class="flex gap-2"><img src="{{ asset('images/quetzal-guia.jpg') }}" alt=""
                    class="h-8 w-8 shrink-0 rounded-full bg-white object-cover object-top">
                <p class="rounded-2xl rounded-tl-sm bg-white p-3 text-sm leading-5 text-slate-700 shadow-sm">Hola, soy Quetzi. Puedo indicarte dónde
                    está cada opción y cómo usar Aprende Guate. No respondo preguntas ajenas a la plataforma.</p>
            </div>
            <div x-show="mode === 'home'" x-transition>
                <div class="rounded-xl border border-sky-200 bg-white p-3">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wide text-sky-700">Recorridos</p>
                            <p class="mt-1 text-xs text-slate-500">Empieza desde el área que quieras conocer.</p>
                        </div><button type="button" @click="startTour('general')"
                            class="rounded-lg bg-sky-700 px-2.5 py-1.5 text-xs font-extrabold text-white hover:bg-sky-800">Recorrido completo</button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2"><template x-for="tour in tours" :key="tour.id"><button type="button"
                                @click="startTour(tour.id)" x-text="tour.title"
                                class="rounded-lg border border-sky-200 px-2.5 py-1.5 text-left text-xs font-semibold text-sky-800 hover:bg-sky-50"></button></template>
                    </div>
                </div>
            </div>
            <div x-cloak x-show="mode === 'tour'" x-transition class="rounded-xl border border-sky-200 bg-white p-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-sky-700" x-text="currentTour?.title"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="currentTour?.description"></p>
                    </div><button type="button" @click="mode = 'home'" class="rounded-lg p-1 text-slate-500 hover:bg-slate-100"
                        aria-label="Cerrar recorrido">×</button>
                </div>
                <div class="mt-4 rounded-xl bg-sky-50 p-3">
                    <p class="text-sm font-extrabold text-slate-900" x-text="currentStep?.title"></p>
                    <p class="mt-2 text-sm leading-5 text-slate-600" x-text="currentStep?.text"></p><a :href="currentStep?.url"
                        x-text="currentStep?.label"
                        class="mt-3 inline-flex rounded-lg bg-sky-700 px-3 py-2 text-xs font-extrabold text-white hover:bg-sky-800"></a>
                </div>
                <div class="mt-3 flex items-center justify-between gap-2"><button type="button" @click="previousStep()" :disabled="stepIndex === 0"
                        class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 disabled:opacity-40">Anterior</button><span
                        class="text-xs font-bold text-slate-500" x-text="(stepIndex + 1) + ' de ' + (currentTour?.steps.length || 0)"></span><button
                        type="button" @click="nextStep()" :disabled="stepIndex >= (currentTour?.steps.length || 1) - 1"
                        class="rounded-lg bg-sky-700 px-2.5 py-1.5 text-xs font-bold text-white disabled:opacity-40">Siguiente</button></div>
            </div>
            <div x-show="answer" x-transition class="flex gap-2"><img src="{{ asset('images/quetzal-guia.jpg') }}" alt=""
                    class="h-8 w-8 shrink-0 rounded-full bg-white object-cover object-top">
                <div class="min-w-0">
                    <p x-text="answer" class="rounded-2xl rounded-tl-sm bg-white p-3 text-sm leading-5 text-slate-700 shadow-sm"></p>
                    <div class="mt-2 flex flex-wrap gap-2"><template x-for="action in actions" :key="action.url"><a :href="action.url"
                                x-text="action.label"
                                class="rounded-lg bg-sky-100 px-2.5 py-1.5 text-xs font-bold text-sky-800 hover:bg-sky-200"></a></template></div>
                </div>
            </div>
            <div x-show="loading" class="flex items-center gap-2 text-xs font-semibold text-sky-700"><span class="ag-spinner"></span> Estoy revisando
                cómo orientarte…</div>
            <div x-show="!answer && !loading" class="flex flex-wrap gap-2"><button type="button"
                    @click="question = '¿Dónde configuro la seguridad y la verificación en dos pasos?'"
                    class="rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-left text-xs font-semibold text-sky-800 hover:bg-sky-50">Configurar
                    seguridad</button><button type="button" @click="question = '¿Cómo entro a mi ruta de aprendizaje?'"
                    class="rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-left text-xs font-semibold text-sky-800 hover:bg-sky-50">Ir a
                    mi ruta</button></div>
            <form @submit.prevent="ask" class="flex items-end gap-2">
                <textarea x-model="question" rows="2" maxlength="500"
                    class="min-w-0 flex-1 resize-none rounded-xl border-sky-200 bg-white text-sm focus:border-sky-500 focus:ring-sky-500"
                    placeholder="¿Cómo uso la plataforma?"></textarea><button type="submit" :disabled="loading || !question.trim()"
                    class="rounded-xl bg-sky-700 px-3 py-2.5 text-xs font-extrabold text-white hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-50">Enviar</button>
            </form>
            <p class="text-[11px] leading-4 text-slate-500">Este guía solo atiende preguntas sobre Aprende Guate.</p>
        </div>
    </section>
    <button x-cloak x-show="!open" x-transition type="button" @click="open = true; window.localStorage.setItem('platform-guide-seen', '1')"
        class="ag-soft-pulse grid h-14 w-14 place-items-center rounded-full border-4 border-white bg-sky-600 shadow-xl shadow-sky-700/25 transition hover:scale-105 hover:bg-sky-700"
        style="display: grid; margin-left: auto; margin-right: 0;" aria-label="Abrir guía de Aprende Guate"><img
            src="{{ asset('images/quetzal-guia.jpg') }}" alt="Abrir guía Quetzi"
            class="h-full w-full rounded-full object-cover object-top"></button>
</div>
<script>
    function platformGuide() {
        return {
            open: false,
            mode: 'home',
            question: '',
            answer: '',
            actions: [],
            loading: false,
            tours: @js($guideTours),
            currentTourId: 'general',
            stepIndex: 0,
            get currentTour() {
                return this.tours.find((tour) => tour.id === this.currentTourId) || this.tours[0] || null;
            },
            get currentStep() {
                return this.currentTour?.steps?.[this.stepIndex] || null;
            },
            init() {
                this.open = window.localStorage.getItem('platform-guide-seen') !== '1';
            },
            close() {
                this.open = false;
                window.localStorage.setItem('platform-guide-seen', '1');
            },
            startTour(id) {
                this.currentTourId = id;
                this.stepIndex = 0;
                this.mode = 'tour';
                window.localStorage.setItem('platform-guide-seen', '1');
            },
            previousStep() {
                if (this.stepIndex > 0) this.stepIndex -= 1;
            },
            nextStep() {
                if (this.currentTour && this.stepIndex < this.currentTour.steps.length - 1) this.stepIndex += 1;
            },
            async ask() {
                const question = this.question.trim();
                if (!question || this.loading) return;
                this.loading = true;
                this.answer = '';
                this.actions = [];
                try {
                    const response = await fetch('{{ route('platform.guide.ask') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            question
                        })
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'No pude responder en este momento.');
                    this.answer = data.answer || 'Puedo ayudarte a navegar por Aprende Guate.';
                    this.actions = data.actions || [];
                    this.question = '';
                } catch (error) {
                    this.answer = error.message || 'No pude responder en este momento.';
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
@endif
@if (auth()->check() && !auth()->user()->hasRole('Admin'))
<div x-data="mentorChatWidget()" x-init="init()" class="fixed bottom-4 right-4 z-[60] w-[min(22rem,calc(100vw-2rem))]" aria-live="polite">
    <section x-cloak x-show="open" x-transition.origin.bottom.right class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <header class="flex items-center justify-between gap-3 bg-blue-700 px-4 py-3 text-white">
            <div class="min-w-0">
                <p class="text-sm font-extrabold">Mensajes de mentoría</p>
                <p class="truncate text-xs text-blue-50" x-text="selected ? selected.counterpart : 'Conversaciones activas'"></p>
            </div>
            <button type="button" @click="setOpen(false)" class="rounded-lg p-1.5 text-blue-50 hover:bg-blue-800 hover:text-white"
                aria-label="Minimizar chat">−</button>
        </header>
        <div class="border-b border-slate-200 bg-slate-50 px-3 py-2" x-show="sessions.length > 1">
            <div class="flex gap-2 overflow-x-auto pb-1">
                <template x-for="session in sessions" :key="session.id">
                    <button type="button" @click="select(session.id)"
                        :class="selectedId === session.id ? 'bg-blue-100 text-blue-800' : 'bg-white text-slate-600'"
                        class="shrink-0 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold" x-text="session.counterpart"></button>
                </template>
            </div>
        </div>
        <div class="max-h-64 min-h-36 space-y-3 overflow-y-auto bg-slate-50 p-4">
            <div x-show="loading" class="flex items-center gap-2 text-sm text-slate-500"><span
                    class="h-2 w-2 animate-pulse rounded-full bg-blue-600"></span> Cargando conversación…</div>
            <template x-if="!loading && !selected">
                <p class="py-6 text-center text-sm text-slate-500">No tienes mentorías activas todavía.</p>
            </template>
            <template x-if="!loading && selected">
                <div>
                    <div x-show="selected.latest" class="flex" :class="selected.latest?.mine ? 'justify-end' : 'justify-start'">
                        <article
                            :class="selected.latest?.mine ? 'rounded-br-sm bg-blue-700 text-white' : 'rounded-bl-sm bg-white text-slate-700 shadow-sm'"
                            class="max-w-[90%] rounded-2xl px-3 py-2.5 text-sm">
                            <p x-text="selected.latest?.body"></p>
                            <p class="mt-1 text-[11px] opacity-70" x-text="selected.latest?.created_at"></p>
                        </article>
                    </div>
                    <p x-show="!selected.latest" class="py-4 text-center text-sm text-slate-500">La conversación comenzará con tu próximo mensaje.</p>
                    <p class="mt-3 text-center text-xs text-slate-500">Solo mostramos el mensaje más reciente aquí.</p>
                </div>
            </template>
            <p x-show="error" x-text="error" class="text-xs text-rose-600"></p>
        </div>
        <form x-show="selected" @submit.prevent="send" class="border-t border-slate-200 bg-white p-3">
            <div class="flex items-end gap-2">
                <textarea x-model="body" rows="2" maxlength="2000"
                    class="min-w-0 flex-1 resize-none rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Escribe un mensaje…"></textarea><button type="submit" :disabled="sending || !body.trim()"
                    class="rounded-xl bg-blue-700 px-3 py-2.5 text-xs font-extrabold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                    x-text="sending ? '…' : 'Enviar'"></button>
            </div>
            <a :href="selected?.history_url || '#'" class="mt-2 inline-block text-xs font-bold text-blue-700 hover:text-blue-900">Ver historial
                completo en Sesiones y chats →</a>
        </form>
    </section>
    <button x-cloak x-show="!open" x-transition type="button" @click="setOpen(true)"
        class="ml-auto grid h-14 w-14 place-items-center rounded-full bg-blue-700 text-xl text-white shadow-xl shadow-blue-700/30 transition hover:scale-105 hover:bg-blue-800"
        aria-label="Abrir mensajes de mentoría"><span aria-hidden="true">✉</span><span x-cloak x-show="unreadTotal > 0"
            x-text="unreadTotal > 9 ? '9+' : unreadTotal"
            class="absolute -right-1 -top-1 grid h-6 min-w-6 place-items-center rounded-full bg-orange-500 px-1 text-xs font-extrabold text-white"></span></button>
</div>
<script>
    function mentorChatWidget() {
        return {
            open: false,
            loading: true,
            sending: false,
            error: '',
            body: '',
            sessions: [],
            selectedId: null,
            poll: null,
            get selected() {
                return this.sessions.find((session) => session.id === this.selectedId) || null;
            },
            get unreadTotal() {
                return this.sessions.reduce((total, session) => total + (session.unread || 0), 0);
            },
            async init() {
                const preference = window.localStorage.getItem('mentor-chat-open');
                await this.refresh();
                // Sin preferencia guardada, solo se abre si hay conversaciones.
                this.open = preference === null ? this.sessions.length > 0 : preference === '1';
                this.poll = window.setInterval(() => this.refresh(), 5000);
            },
            setOpen(value) {
                this.open = value;
                window.localStorage.setItem('mentor-chat-open', value ? '1' : '0');
                if (value && this.selectedId) this.markRead(this.selectedId);
            },
            async select(id) {
                this.selectedId = id;
                this.error = '';
                await this.markRead(id);
            },
            async refresh() {
                try {
                    const response = await fetch('{{ route('mentorship.widget') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    this.sessions = data.sessions || [];
                    if (!this.sessions.some((session) => session.id === this.selectedId)) this.selectedId = this.sessions[0]?.id || null;
                    if (this.open && this.selectedId) await this.markRead(this.selectedId);
                } catch (error) {
                    this.error = 'No se pudo actualizar el chat.';
                } finally {
                    this.loading = false;
                }
            },
            async markRead(id) {
                const session = this.sessions.find((item) => item.id === id);
                if (!session || !session.unread) return;
                try {
                    await fetch(`{{ url('/mentorias/widget') }}/${id}/leer`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    session.unread = 0;
                } catch (error) {
                    /* El indicador se conserva hasta que el servidor confirme la lectura. */ }
            },
            async send() {
                if (!this.selected || !this.body.trim() || this.sending) return;
                this.sending = true;
                this.error = '';
                try {
                    const response = await fetch(this.selected.send_url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            body: this.body.trim()
                        })
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'No se pudo enviar el mensaje.');
                    this.body = '';
                    await this.refresh();
                } catch (error) {
                    this.error = error.message || 'No se pudo enviar el mensaje.';
                } finally {
                    this.sending = false;
                }
            },
        };
    }
</script>
@endif
</body>

</html>
