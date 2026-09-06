@extends('layouts.admin')

@section('content')
    <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-8">Editar Categoría</h1>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre de la Categoría</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" required>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('admin.categories.index') }}" class="rounded-md bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Cancelar</a>
                <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Actualizar Categoría</button>
            </div>
        </form>
    </div>
@endsection
