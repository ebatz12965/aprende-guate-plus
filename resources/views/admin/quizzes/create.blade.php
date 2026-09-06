@extends('layouts.admin')

@section('content')
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-8">
        Añadir Cuestionario a "{{ $class->name }}"
    </h2>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form action="{{ route('admin.classes.quizzes.store', $class) }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Título del Cuestionario</label>
                    <input type="text" name="title" id="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" required>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Descripción (Opcional)</label>
                    <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"></textarea>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('admin.classes.show', $class) }}" class="rounded-md bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Cancelar</a>
                <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Guardar Cuestionario</button>
            </div>
        </form>
    </div>
@endsection
