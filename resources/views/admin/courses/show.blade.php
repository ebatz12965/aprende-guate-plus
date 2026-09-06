@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $course->title }}</h2>
            <p class="mt-1 text-sm text-gray-500">Gestiona los módulos, clases y estudiantes de tu curso.</p>
        </div>
        <div class="flex items-center gap-x-4">
            <a href="{{ route('admin.courses.index') }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Volver a Cursos
            </a>
            <a href="{{ route('admin.courses.edit', $course) }}" class="rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                Editar Curso
            </a>
            <form action="{{ route('admin.courses.toggleStatus', $course) }}" method="POST">
                @csrf
                @if($course->status == 'draft')
                    <button type="submit" class="rounded-md bg-green-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Publicar Curso
                    </button>
                @else
                    <button type="submit" class="rounded-md bg-yellow-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-yellow-400">
                        Pasar a Borrador
                    </button>
                @endif
            </form>
        </div>
    </div>

    <div class="border-b border-gray-200 mb-8">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="#contenido" class="tab-link border-sky-500 text-sky-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Contenido</a>
            <a href="#estudiantes" class="tab-link border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Estudiantes</a>
        </nav>
    </div>

    <div id="contenido" class="tab-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold tracking-tight text-gray-900">Módulos del Curso</h3>
            <a href="{{ route('admin.courses.modules.create', $course) }}" class="rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                Añadir Módulo
            </a>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($course->modules as $module)
                    <li class="p-4 sm:p-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-lg font-semibold text-gray-800">{{ $module->name }}</p>
                                <p class="text-sm text-gray-500">{{ $module->classes->count() }} Clases</p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                <a href="{{ route('admin.modules.show', $module) }}" class="font-medium text-sky-600 hover:text-sky-800 mr-4">Gestionar Clases</a>
                                <a href="{{ route('admin.modules.edit', $module) }}" class="font-medium text-gray-600 hover:text-gray-800 mr-4">Editar</a>
                                <form action="{{ route('admin.modules.destroy', $module) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar este módulo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 hover:text-red-800">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </li>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        <h3 class="text-lg font-medium">Este curso aún no tiene módulos</h3>
                        <p class="mt-1 text-sm">¡Añade el primer módulo para empezar a construir tu curso!</p>
                    </div>
                @endforelse
            </ul>
        </div>
    </div>

    <div id="estudiantes" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <h3 class="text-2xl font-bold tracking-tight text-gray-900 mb-6">Estudiantes Inscritos ({{ $course->students->count() }})</h3>
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <ul role="list" class="divide-y divide-gray-200">
                        @forelse($course->students as $student)
                            <li class="p-4 sm:p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&color=7F9CF5&background=EBF4FF" alt="">
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $student->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                        </div>
                                    </div>
                                    <form action="{{ route('admin.enrollments.destroy', ['course' => $course, 'user' => $student]) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas anular la inscripción de este estudiante?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Anular Inscripción</button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="p-6 text-center text-gray-500">
                                Aún no hay estudiantes inscritos en este curso.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-bold tracking-tight text-gray-900 mb-6">Inscribir Estudiante</h3>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <form action="{{ route('admin.enrollments.store', $course) }}" method="POST">
                        @csrf
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">Seleccionar Estudiante</label>
                            <select id="user_id" name="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" {{ $potentialStudents->isEmpty() ? 'disabled' : '' }}>
                                @forelse($potentialStudents as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @empty
                                    <option disabled>No hay nuevos estudiantes para inscribir</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="w-full rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700" {{ $potentialStudents->isEmpty() ? 'disabled' : '' }}>Inscribir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.tab-link');
            const sections = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', e => {
                    e.preventDefault();
                    const targetId = e.target.getAttribute('href').substring(1);

                    tabs.forEach(t => {
                        t.classList.remove('border-sky-500', 'text-sky-600');
                        t.classList.add('border-transparent', 'text-gray-500');
                    });
                    e.target.classList.add('border-sky-500', 'text-sky-600');

                    sections.forEach(s => {
                        if (s.id === targetId) {
                            s.classList.remove('hidden');
                        } else {
                            s.classList.add('hidden');
                        }
                    });
                });
            });
        });
    </script>
@endsection
