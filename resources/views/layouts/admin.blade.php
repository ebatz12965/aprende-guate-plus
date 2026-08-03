<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        <aside class="bg-gray-800 text-white w-64 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-30"
               :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
            <div class="p-4 text-2xl font-bold">
                <a href="{{ route('admin.dashboard') }}">GuateAprende</a>
            </div>
            <nav class="mt-8">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">Dashboard</a>
                <a href="{{ route('admin.courses.index') }}" class="flex items-center mt-2 px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">Cursos</a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center mt-2 px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">Usuarios</a>
                <a href="{{ route('admin.categories.index') }}" class="flex items-center mt-2 px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">Categorías</a>
                <a href="{{ route('admin.levels.index') }}" class="flex items-center mt-2 px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">Niveles</a>
                <a href="{{ route('admin.roles.index') }}" class="flex items-center mt-2 px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">Roles</a>
            </nav>
        </aside>

        <!-- Contenido Principal -->
        <div class="md:ml-64 flex flex-col flex-1">
            <header class="bg-white shadow-sm flex justify-between items-center p-4 md:justify-end">
                <!-- Botón para abrir sidebar en móvil -->
                <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>

                <!-- Menú de Usuario -->
                <div x-data="{ dropdownOpen: false }" class="relative">
                    <button @click="dropdownOpen = !dropdownOpen" class="relative block h-8 w-8 rounded-full overflow-hidden focus:outline-none">
                        <img class="h-full w-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&color=7F9CF5&background=EBF4FF" alt="Tu avatar">
                    </button>
                    <div x-show="dropdownOpen" @click.away="dropdownOpen = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md overflow-hidden shadow-xl z-10" x-transition>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-sky-600 hover:text-white">Mi Perfil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="#" onclick="event.preventDefault(); this.closest('form').submit();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-sky-600 hover:text-white">Cerrar Sesión</a>
                        </form>
                    </div>
                </div>
            </header>

            <main class="p-6 md:p-8">
                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
