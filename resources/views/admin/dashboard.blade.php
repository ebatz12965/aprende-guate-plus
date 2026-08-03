@extends('layouts.platform')

@section('content')
    <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-8">
        Dashboard
    </h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Usuarios -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-800">Usuarios Registrados</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ $users }}
            </p>
        </div>

        <!-- Cursos -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-800">Cursos</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                #
            </p>
        </div>

        <!-- Categorías -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-800">Categorías</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                #
            </p>
        </div>
    </div>
@endsection
