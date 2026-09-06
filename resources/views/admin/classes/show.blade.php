@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $class->name }}</h2>
            <p class="mt-1 text-sm text-gray-500">Clase del módulo: {{ $class->module->name }}</p>
        </div>
        <div class="flex items-center gap-3">
        <a href="{{ route('admin.classes.edit', $class) }}" class="rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-800">
            Editar clase
        </a>
        <a href="{{ route('admin.modules.show', $class->module) }}" class="rounded-xl bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Volver al Módulo
        </a>
        </div>
    </div>

    {{-- Contenido de la Clase --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Contenido de la Clase</h3>
        @if($class->hls_path && $class->video_status === 'ready')
            <div class="mb-5 aspect-video overflow-hidden rounded-xl bg-slate-950 shadow-lg">
                <video class="h-full w-full" controls preload="metadata" data-hls-source="{{ \Illuminate\Support\Facades\Storage::disk('courses')->url($class->hls_path) }}"></video>
            </div>
        @elseif($class->video_status === 'queued' || $class->video_status === 'processing')
            <div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">El video se está preparando en varias calidades.</div>
        @elseif($class->video_status === 'failed')
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">No se pudo procesar el video. Vuelve a cargarlo o revisa el worker de cola.</div>
        @elseif($class->video_url)
            <div class="aspect-w-16 aspect-h-9 mb-4">
                <iframe src="{{ str_replace('watch?v=', 'embed/', $class->video_url) }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full rounded-lg"></iframe>
            </div>
        @endif
        <div class="prose max-w-none">
            {!! nl2br(e($class->content)) !!}
        </div>
    </div>

    {{-- Sección de Tareas --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold tracking-tight text-gray-900">Tareas de la Clase</h3>
            <a href="{{ route('admin.classes.tasks.create', $class) }}" class="rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">
                Añadir Tarea
            </a>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            @if($class->tasks->count() > 0)
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($class->tasks as $task)
                        <li class="p-4 sm:p-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-lg font-semibold text-gray-800">{{ $task->title }}</p>
                                    <p class="text-sm text-gray-500">Fecha de entrega: {{ $task->due_date ? $task->due_date->format('d/m/Y') : 'No definida' }}</p>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <a href="{{ route('admin.tasks.show', $task) }}" class="font-medium text-sky-600 hover:text-sky-800 mr-4">Ver Entregas</a>
                                    <a href="{{ route('admin.tasks.edit', $task) }}" class="font-medium text-gray-600 hover:text-gray-800 mr-4">Editar</a>
                                    <form action="{{ route('admin.tasks.destroy', $task) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar esta tarea?');">
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
                    <p>Esta clase aún no tiene tareas.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Sección de Cuestionarios --}}
    <div>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold tracking-tight text-gray-900">Cuestionarios de la Clase</h3>
            <a href="{{ route('admin.classes.quizzes.create', $class) }}" class="rounded-md bg-sky-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">
                Añadir Cuestionario
            </a>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            @if($class->quizzes->count() > 0)
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($class->quizzes as $quiz)
                        <li class="p-4 sm:p-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-lg font-semibold text-gray-800">{{ $quiz->title }}</p>
                                    <p class="text-sm text-gray-500">{{ $quiz->questions->count() }} preguntas</p>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="font-medium text-gray-600 hover:text-gray-800 mr-4">Editar Preguntas</a>
                                    <a href="{{ route('admin.quizzes.attempts', $quiz) }}" class="font-medium text-sky-600 hover:text-sky-800">Ver Calificaciones</a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-6 text-center text-gray-500">
                    <p>Esta clase aún no tiene cuestionarios.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
