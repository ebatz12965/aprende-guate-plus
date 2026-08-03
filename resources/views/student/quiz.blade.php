@extends('layouts.platform')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $quiz->title }}</h2>
            <p class="mt-1 text-sm text-gray-500">Curso: {{ $quiz->schoolClass->module->course->title }}</p>
        </div>
        <a href="{{ route('student.course.study', $quiz->schoolClass->module->course) }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Volver al Curso
        </a>
    </div>

    @if ($attempt)
        {{-- Vista de Resultados --}}
        <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
            <h3 class="text-2xl font-semibold text-gray-800 mb-2">Ya has completado este cuestionario</h3>
            <p class="text-lg text-gray-600 mb-4">Tu puntuación fue:</p>
            <p class="text-6xl font-bold text-sky-600">{{ $attempt->score }}%</p>
            <p class="mt-6 text-gray-500">Puedes revisar las preguntas y tus respuestas, pero no puedes volver a enviar el cuestionario.</p>
        </div>
    @endif

    <form action="{{ route('student.quiz.submit', $quiz) }}" method="POST" class="mt-8">
        @csrf
        <div class="space-y-10">
            @foreach ($quiz->questions as $question)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-lg font-semibold text-gray-900 mb-4">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                    <fieldset class="space-y-4">
                        <legend class="sr-only">Opciones para la pregunta {{ $loop->iteration }}</legend>
                        @foreach ($question->options as $option)
                            <div class="flex items-center">
                                <input id="option_{{ $option->id }}" name="answers[{{ $question->id }}]" type="radio" value="{{ $option->id }}" class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500" {{ $attempt ? 'disabled' : '' }}>
                                <label for="option_{{ $option->id }}" class="ml-3 block text-sm font-medium text-gray-700">{{ $option->option_text }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                </div>
            @endforeach
        </div>

        @if (!$attempt)
            <div class="mt-8 flex justify-end">
                <button type="submit" class="rounded-md border border-transparent bg-sky-600 py-3 px-6 text-base font-medium text-white shadow-sm hover:bg-sky-700">Finalizar Cuestionario</button>
            </div>
        @endif
    </form>
@endsection
