@extends('layouts.platform')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $task->title }}</h2>
            <p class="mt-1 text-sm text-gray-500">Curso: {{ $task->schoolClass->module->course->title }}</p>
        </div>
        <a href="{{ route('student.course.study', $task->schoolClass->module->course) }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Volver al Curso
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Columna con la descripción de la tarea y la entrega --}}
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Instrucciones</h3>
                <div class="prose max-w-none">
                    {!! nl2br(e($task->description)) !!}
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">{{ $submission ? 'Tu Entrega' : 'Realizar Entrega' }}</h3>
                <form action="{{ route('student.task.submit', $task) }}" method="POST">
                    @csrf
                    <textarea name="content" id="content" rows="12" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" {{ $submission ? 'disabled' : '' }}>{{ old('content', $submission->content ?? '') }}</textarea>

                    @if (!$submission)
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700">Enviar Tarea</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- Columna con la calificación --}}
        <div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6 sticky top-10">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Calificación</h3>
                @if ($submission && $submission->grade)
                    <div class="text-center">
                        <p class="text-6xl font-bold text-sky-600">{{ number_format($submission->grade, 2) }}</p>
                        <p class="text-sm text-gray-500">sobre 100.00</p>
                    </div>
                    @if($submission->feedback)
                        <div class="mt-6 border-t pt-4">
                            <h4 class="font-semibold text-gray-800 mb-2">Comentarios del Instructor</h4>
                            <div class="prose prose-sm max-w-none">
                                {!! nl2br(e($submission->feedback)) !!}
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-center text-gray-500 py-8">Tu tarea aún no ha sido calificada.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
