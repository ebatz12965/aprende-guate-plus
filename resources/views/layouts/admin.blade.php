<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Administración | Aprende Guate</title>
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/aprende-guate-app-icon.png') }}">
    <script>if (localStorage.getItem('admin-theme') === 'dark') document.documentElement.classList.add('dark');</script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
@php
    $navItemClass = fn (string|array $pattern) => request()->routeIs($pattern)
        ? 'admin-sidebar-link admin-sidebar-link-active'
        : 'admin-sidebar-link';
    $twoFactorPending = auth()->check() && ! auth()->user()->hasTwoFactorEnabled();
@endphp
<div x-data="{
        sidebarOpen: false,
        profileOpen: false,
        toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('admin-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }
    }" class="min-h-screen">
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-sky-100 bg-white transition-transform duration-200 lg:translate-x-0">
        <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 text-sky-950 dark:text-white">
                <img src="{{ asset('images/aprende-guate-app-icon.png') }}" alt="" class="ag-brand-icon">
                <span><span class="block text-base font-extrabold tracking-tight">Aprende Guate</span><span class="block text-xs font-medium text-blue-700 dark:text-blue-300">Administración</span></span>
            </a>
            <button @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Cerrar menú">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-6">
            <p class="admin-sidebar-label">VISTA GENERAL</p>
            <a href="{{ route('admin.dashboard') }}" class="{{ $navItemClass(['admin.dashboard', 'admin.ai-pulse.*']) }}">
                <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Panel
            </a>

            @role('Admin')
                <p class="admin-sidebar-label mt-7">GESTIÓN</p>
                <a href="{{ route('admin.users.index') }}" class="{{ $navItemClass('admin.users.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Usuarios
                </a>
                <a href="{{ route('admin.mentor-applications.index') }}" class="{{ $navItemClass('admin.mentor-applications.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M2 21a7 7 0 0 1 14 0M19 8v6m3-3h-6"/></svg>Solicitudes de mentor
                </a>
                <a href="{{ route('admin.roles.index') }}" class="{{ $navItemClass('admin.roles.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15.5 7.5 18l1.2-5-3.8-3.2 5-.4L12 4.7l2.1 4.7 5 .4-3.8 3.2 1.2 5z"/></svg>Roles
                </a>

                <p class="admin-sidebar-label mt-7">INTELIGENCIA ARTIFICIAL</p>
                <a href="{{ route('admin.ai-settings.edit') }}" class="{{ $navItemClass('admin.ai-settings.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h.01M15 9h.01M9 15h6"/></svg>Límites de IA
                </a>
                <a href="{{ route('admin.ai-usage.index') }}" class="{{ $navItemClass('admin.ai-usage.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5m5 14V9m5 10V3m5 16v-7"/></svg>Consumo de IA
                </a>
                <a href="{{ route('admin.ai-orchestrator.index') }}" class="{{ $navItemClass('admin.ai-orchestrator.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M4.9 4.9 7 7m10 10 2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/></svg>Orquestador IA
                </a>

                <p class="admin-sidebar-label mt-7">CONFIGURACIÓN</p>
                <a href="{{ route('admin.mail-settings.edit') }}" class="{{ $navItemClass('admin.mail-settings.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>Correo saliente
                </a>                <a href="{{ route('admin.announcements.index') }}" class="{{ $navItemClass('admin.announcements.*') }}">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>Avisos
                </a>
            @endrole
        </nav>

        <div class="border-t border-slate-100 p-4">
            <a href="{{ route('adaptive.dashboard') }}" class="admin-student-link">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 19 5-5 4 4 7-8"/><path d="M15 10h5v5"/></svg>
                Ver experiencia estudiante
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf
                <button class="admin-sidebar-link w-full text-left text-rose-700 hover:bg-rose-50 hover:text-rose-800">
                    <svg class="admin-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="admin-topbar sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="rounded-xl p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div><p class="text-sm font-semibold text-slate-900">Panel administrativo</p><p class="hidden text-xs text-slate-500 sm:block">Gestiona el contenido y la configuración de Aprende Guate</p></div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <button @click="toggleTheme()" class="admin-theme-toggle" aria-label="Cambiar tema">
                    <svg class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                    <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>
                    <span class="hidden sm:inline">Tema</span>
                </button>

                <div class="relative">
                    <button @click="profileOpen = !profileOpen" @keydown.escape.window="profileOpen = false" :aria-expanded="profileOpen" aria-haspopup="menu" class="flex items-center gap-2 rounded-xl p-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-100 text-blue-800">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>
                        @if($twoFactorPending)<span class="h-2.5 w-2.5 rounded-full bg-orange-500 ring-2 ring-white" title="Verificación en dos pasos pendiente"></span>@endif
                        <span class="hidden max-w-32 truncate sm:inline">{{ auth()->user()->name }}</span>
                        <svg class="hidden h-4 w-4 text-slate-500 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-cloak x-show="profileOpen" @click.outside="profileOpen = false" x-transition class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-900/10" role="menu">
                        <p class="px-3 py-2 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Cambiar espacio</p>
                        <form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space" value="learning"><button class="platform-menu-link w-full text-left" role="menuitem">Mi aprendizaje</button></form>
                        @if(auth()->user()->hasRole('Instructor'))<form method="POST" action="{{ route('workspace.switch') }}">@csrf<input type="hidden" name="space" value="mentor"><button class="platform-menu-link w-full text-left" role="menuitem">Espacio mentor</button></form>@endif
                        <div class="my-1 border-t border-slate-100"></div>
                        <a href="{{ route('notifications.index') }}" class="platform-menu-link" role="menuitem">Notificaciones @if($twoFactorPending)<span class="float-right mt-1 h-2.5 w-2.5 rounded-full bg-orange-500" title="Verificación en dos pasos pendiente"></span>@endif</a><a href="{{ route('profile.edit') }}" class="platform-menu-link" role="menuitem">Configuración</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="platform-menu-link w-full text-left text-rose-700 hover:bg-rose-50 hover:text-rose-800" role="menuitem">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="admin-content mx-auto w-full max-w-[1600px] px-4 py-7 sm:px-6 lg:px-8 lg:py-10">
            @if (session('success'))<div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ session('success') }}</div>@endif
            @if (session('error'))<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
        @include('components.platform-footer')
    </div>
</div>
</body>
</html>
