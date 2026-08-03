@extends('layouts.platform')

@section('content')
<div x-data="{ selectedClass: {{ $course->modules->flatMap->classes->first()->id ?? 0 }} }">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Barra Lateral de Navegación --}}
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 sticky top-10">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ $course->title }}</h3>
                <ul class="space-y-4">
                    @foreach($course->modules as $module)
                        <li>
                            <h4 class="font-semibold text-gray-700 mb-2">{{ $module->name }}</h4>
                            <ul class="space-y-2 pl-4 border-l border-gray-200">
                                @foreach($module->classes as $class)
                                    <li>
                                        <a href="#"
                                           @click.prevent="selectedClass = {{ $class->id }}"
                                           class="flex items-center"
                                           :class="{ 'text-sky-600 font-bold': selectedClass === {{ $class->id }}, 'text-gray-500 hover:text-gray-800': selectedClass !== {{ $class->id }} }">
                                            @if(!$class->progress->isEmpty())
                                                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            @endif
                                            <span>{{ $class->name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Área de Contenido Principal --}}
        <div class="lg:col-span-3">
            @foreach($course->modules->flatMap->classes as $class)
                <div x-show="selectedClass === {{ $class->id }}" x-transition>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                        <div class="flex justify-between items-start">
                            <h2 class="text-3xl font-bold tracking-tight text-gray-900 mb-4">{{ $class->name }}</h2>
                            <form action="{{ route('student.class.complete', $class) }}" method="POST">
                                @csrf
                                @if(!$class->progress->isEmpty())
                                    <button type="button" class="rounded-md bg-green-100 px-3 py-2 text-sm font-semibold text-green-700 shadow-sm" disabled>
                                        Completada
                                    </button>
                                @else
                                    <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                        Marcar como Completada
                                    </button>
                                @endif
                            </form>
                        </div>

                        @if($class->hls_path && $class->video_status === 'ready')
                            <div class="my-6 aspect-video overflow-hidden rounded-xl bg-slate-950 shadow-lg">
                                <video class="h-full w-full" controls preload="metadata" data-hls-source="{{ \Illuminate\Support\Facades\Storage::disk('courses')->url($class->hls_path) }}"></video>
                            </div>
                        @elseif($class->video_status === 'queued' || $class->video_status === 'processing')
                            <div class="my-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">El video se está preparando en varias calidades. Estará disponible pronto.</div>
                        @elseif($class->video_url)
                            <div class="aspect-w-16 aspect-h-9 my-6">
                                <iframe src="{{ str_replace('watch?v=', 'embed/', $class->video_url) }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full rounded-lg shadow-lg"></iframe>
                            </div>
                        @endif

                        @if($class->content)
                            <div class="prose max-w-none text-gray-700 mt-6">
                                {!! nl2br(e($class->content)) !!}
                            </div>
                        @endif
                    </div>

                    {{-- Tareas de la Clase --}}
                    @if($class->tasks->count() > 0)
                        <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                            <h3 class="text-2xl font-bold tracking-tight text-gray-900 mb-4">Tareas</h3>
                            <ul class="divide-y divide-gray-200">
                                @foreach($class->tasks as $task)
                                    <li class="py-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-lg font-semibold text-gray-800">{{ $task->title }}</h4>
                                                <p class="text-sm text-gray-600">Fecha de entrega: {{ $task->due_date ? $task->due_date->format('d/m/Y') : 'No definida' }}</p>
                                            </div>
                                            <a href="{{ route('student.task.view', $task) }}" class="rounded-md bg-sky-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-500">Ver Tarea</a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Cuestionarios de la Clase --}}
                    @if($class->quizzes->count() > 0)
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-2xl font-bold tracking-tight text-gray-900 mb-4">Cuestionarios</h3>
                            <ul class="divide-y divide-gray-200">
                                @foreach($class->quizzes as $quiz)
                                    <li class="py-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-lg font-semibold text-gray-800">{{ $quiz->title }}</h4>
                                                <p class="text-sm text-gray-600">{{ $quiz->description }}</p>
                                            </div>
                                            <a href="{{ route('student.quiz.take', $quiz) }}" class="rounded-md bg-sky-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600">Realizar Cuestionario</a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
