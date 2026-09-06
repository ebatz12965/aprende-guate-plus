@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $quiz->title }}</h2>
            <p class="mt-1 text-sm text-gray-500">Gestiona las preguntas y opciones de tu cuestionario.</p>
        </div>
        <a href="{{ route('admin.classes.show', $quiz->schoolClass) }}" class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Volver a la Clase
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Columna de Preguntas --}}
        <div class="lg:col-span-2">
            <h3 class="text-2xl font-bold tracking-tight text-gray-900 mb-6">Preguntas del Cuestionario</h3>
            <div class="space-y-6">
                @forelse($quiz->questions as $question)
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <p class="text-gray-800 font-medium">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                            <div>
                                <a href="{{ route('admin.questions.edit', $question) }}" class="text-sm font-medium text-sky-600 hover:text-sky-800">Editar</a>
                                <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" class="inline-block ml-4" onsubmit="return confirm('¿Seguro que deseas eliminar esta pregunta?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <ul class="mt-4 space-y-2 pl-4">
                            @foreach($question->options as $option)
                                <li class="flex items-center text-sm {{ $option->is_correct ? 'font-bold text-green-600' : 'text-gray-600' }}">
                                    @if($option->is_correct)
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    @else
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                    @endif
                                    {{ $option->option_text }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                        <p class="text-gray-500">Este cuestionario aún no tiene preguntas.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Columna para Añadir Pregunta --}}
        <div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6 sticky top-10">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Añadir Nueva Pregunta</h3>
                <form action="{{ route('admin.quizzes.questions.store', $quiz) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="question_text" class="block text-sm font-medium text-gray-700">Texto de la Pregunta</label>
                            <textarea name="question_text" id="question_text" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" required>{{ old('question_text') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Opciones</label>
                            <p class="text-xs text-gray-500">Rellena al menos 2 opciones y marca la correcta.</p>
                            @for ($i = 1; $i <= 4; $i++)
                            <div class="mt-2 flex rounded-md shadow-sm">
                                <div class="relative flex items-center pl-3 bg-gray-50 border border-r-0 border-gray-300 rounded-l-md">
                                    <input id="is_correct_{{ $i }}" name="is_correct" type="radio" value="{{ $i }}" class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500" {{ old('is_correct', 1) == $i ? 'checked' : '' }}>
                                </div>
                                <input type="text" name="options[{{ $i }}]" value="{{ old('options.'.$i) }}" class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-gray-300 focus:ring-sky-500 focus:border-sky-500 sm:text-sm" placeholder="Texto de la opción {{ $i }}">
                            </div>
                            @endfor
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700">Añadir Pregunta</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
