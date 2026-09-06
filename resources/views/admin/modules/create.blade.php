@extends('layouts.admin')

@section('content')
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-8">
        Añadir Módulo a "{{ $course->title }}"
    </h2>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form action="{{ route('admin.courses.modules.store', $course) }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Módulo</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" required>
                </div>

            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('admin.courses.show', $course) }}" class="rounded-md bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Cancelar</a>
                <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Guardar Módulo</button>
            </div>
        </form>
    </div>
@endsection
