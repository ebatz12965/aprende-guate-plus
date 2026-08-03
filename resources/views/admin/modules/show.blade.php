@extends('layouts.platform')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $module->name }}</h2>
            <p class="mt-1 text-sm text-gray-500">Gestiona las clases del módulo.</p>
        </div>
        <a href="{{ route('admin.courses.show', $module->course) }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Volver al Curso
        </a>
    </div>

    <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold tracking-tight text-gray-900">Clases del Módulo</h3>
            <a href="{{ route('admin.modules.classes.create', $module) }}" class="rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                Añadir Clase
            </a>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            @if($module->classes->count() > 0)
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($module->classes as $class)
                        <li class="p-4 sm:p-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <a href="{{ route('admin.classes.show', $class) }}" class="text-lg font-semibold text-gray-800 hover:text-sky-600">{{ $class->name }}</a>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <a href="{{ route('admin.classes.edit', $class) }}" class="font-medium text-gray-600 hover:text-gray-800 mr-4">Editar</a>
                                    <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar esta clase?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-800">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-6 text-center text-gray-500">
                    <h3 class="text-lg font-medium">Este módulo aún no tiene clases</h3>
                    <p class="mt-1 text-sm">¡Añade la primera clase para completar tu módulo!</p>
                </div>
            @endif
        </div>
    </div>
@endsection
