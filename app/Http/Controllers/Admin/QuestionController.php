<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function store(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string',
            'is_correct' => 'required|in:' . implode(',', array_keys($request->options ?? [])),
        ]);

        try {
            DB::transaction(function () use ($request, $quiz) {
                $question = $quiz->questions()->create(['question_text' => $request->question_text]);
                foreach ($request->options as $key => $optionText) {
                    $question->options()->create([
                        'option_text' => $optionText,
                        'is_correct' => $key == $request->is_correct,
                    ]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->route('admin.quizzes.show', $quiz)->with('error', 'Hubo un error al crear la pregunta.');
        }

        return redirect()->route('admin.quizzes.show', $quiz)->with('success', 'Pregunta añadida correctamente.');
    }

    /**
     * Muestra el formulario para editar una pregunta existente.
     */
    public function edit(Question $question)
    {
        $question->load('options');
        return view('admin.questions.edit', compact('question'));
    }

    /**
     * Actualiza una pregunta y sus opciones en la base de datos.
     */
    public function update(Request $request, Question $question)
    {
        $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array',
            'options.*' => 'required|string',
            'is_correct' => 'required|exists:question_options,id',
        ]);

        try {
            DB::transaction(function () use ($request, $question) {
                $question->update(['question_text' => $request->question_text]);

                foreach ($request->options as $optionId => $optionText) {
                    QuestionOption::where('id', $optionId)->update([
                        'option_text' => $optionText,
                        'is_correct' => $optionId == $request->is_correct,
                    ]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->route('admin.quizzes.show', $question->quiz)->with('error', 'Hubo un error al actualizar la pregunta.');
        }

        return redirect()->route('admin.quizzes.show', $question->quiz)->with('success', 'Pregunta actualizada correctamente.');
    }

    /**
     * Elimina una pregunta de la base de datos.
     */
    public function destroy(Question $question)
    {
        $quiz = $question->quiz;
        $question->delete(); // Las opciones se eliminan en cascada gracias a la configuración de la BD
        return redirect()->route('admin.quizzes.show', $quiz)->with('success', 'Pregunta eliminada correctamente.');
    }
}
