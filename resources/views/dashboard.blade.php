@extends('layouts.platform')

@section('content')
    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight mb-6">
        Panel de Control
    </h2>

    <div class="text-gray-900">
        <p>¡Has iniciado sesión correctamente!</p>

        <div class="mt-6 border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Accesos Rápidos</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-gray-50 p-4 hover:bg-gray-100 transition">
                    <h4 class="font-semibold text-gray-800">Usuarios</h4>
                    <p class="text-sm text-gray-600">Gestionar usuarios y permisos.</p>
                </a>
                <a href="{{ route('admin.courses.index') }}" class="rounded-lg bg-gray-50 p-4 hover:bg-gray-100 transition">
                    <h4 class="font-semibold text-gray-800">Cursos</h4>
                    <p class="text-sm text-gray-600">Administrar los cursos de la plataforma.</p>
                </a>
                <a href="{{ route('admin.roles.index') }}" class="rounded-lg bg-gray-50 p-4 hover:bg-gray-100 transition">
                    <h4 class="font-semibold text-gray-800">Roles</h4>
                    <p class="text-sm text-gray-600">Definir roles de usuario.</p>
                </a>
                <a href="{{ route('admin.permissions.index') }}" class="rounded-lg bg-gray-50 p-4 hover:bg-gray-100 transition">
                    <h4 class="font-semibold text-gray-800">Permisos</h4>
                    <p class="text-sm text-gray-600">Configurar permisos detallados.</p>
                </a>
                 <a href="{{route('admin.dashboard')}}" class="rounded-lg bg-red-100 p-4 hover:bg-red-200 transition">
                    <h4 class="font-semibold text-red-800">Panel Administrativo</h4>
                    <p class="text-sm text-red-600">Acceder al panel de administración.</p>
                </a>
            </div>
        </div>
    </div>
@endsection
