@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Calificar Entrega</h2>
            <p class="mt-1 text-sm text-gray-500">Tarea: {{ $submission->task->title }}</p>
            <p class="mt-1 text-sm text-gray-500">Estudiante: {{ $submission->student->name }}</p>
        </div>
        <a href="{{ route('admin.tasks.show', $submission->task) }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Volver a la Tarea
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Columna con la entrega del estudiante --}}
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Contenido de la Entrega</h3>
                <div class="prose max-w-none">
                    {!! nl2br(e($submission->content)) !!}
                </div>
            </div>
        </div>

        {{-- Columna con el formulario de calificación --}}
        <div>
            <form action="{{ route('admin.submissions.update', $submission) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Calificación</h3>
                    <div class="space-y-6">
                        <div>
                            <label for="grade" class="block text-sm font-medium text-gray-700">Nota (sobre 100)</label>
                            <input type="number" name="grade" id="grade" value="{{ old('grade', $submission->grade) }}" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="feedback" class="block text-sm font-medium text-gray-700">Comentarios / Feedback</label>
                            <textarea name="feedback" id="feedback" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm">{{ old('feedback', $submission->feedback) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="w-full rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700">Guardar Calificación</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
