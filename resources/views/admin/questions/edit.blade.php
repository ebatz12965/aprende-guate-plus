@extends('layouts.platform')

@section('content')
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-8">
        Editar Pregunta
    </h2>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form action="{{ route('admin.questions.update', $question) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div>
                    <label for="question_text" class="block text-sm font-medium text-gray-700">Texto de la Pregunta</label>
                    <textarea name="question_text" id="question_text" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm" required>{{ old('question_text', $question->question_text) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Opciones</label>
                    <p class="text-xs text-gray-500">Modifica las opciones y asegúrate de que una esté marcada como correcta.</p>
                    @foreach ($question->options as $index => $option)
                    <div class="mt-2 flex rounded-md shadow-sm">
                        <div class="relative flex items-center pl-3 bg-gray-50 border border-r-0 border-gray-300 rounded-l-md">
                            <input id="is_correct_{{ $option->id }}" name="is_correct" type="radio" value="{{ $option->id }}" class="h-4 w-4 text-sky-600 border-gray-300 focus:ring-sky-500" {{ $option->is_correct ? 'checked' : '' }}>
                        </div>
                        <input type="text" name="options[{{ $option->id }}]" value="{{ old('options.'.$option->id, $option->option_text) }}" class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-gray-300 focus:ring-sky-500 focus:border-sky-500 sm:text-sm" required>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('admin.quizzes.show', $question->quiz) }}" class="rounded-md bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Cancelar</a>
                <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Actualizar Pregunta</button>
            </div>
        </form>
    </div>
@endsection
