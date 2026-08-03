<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function create(SchoolClass $class)
    {
        return view('admin.quizzes.create', compact('class'));
    }

    public function store(Request $request, SchoolClass $class)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $quiz = new Quiz($request->all());
        $class->quizzes()->save($quiz);

        return redirect()->route('admin.quizzes.show', $quiz)->with('success', 'Cuestionario creado. Ahora puedes añadir las preguntas.');
    }

    public function show(Quiz $quiz)
    {
        $quiz->load('questions.options');
        return view('admin.quizzes.show', compact('quiz'));
    }

    /**
     * Muestra la página de calificaciones para un cuestionario específico.
     */
    public function viewAttempts(Quiz $quiz)
    {
        $quiz->load('schoolClass.module.course.students', 'attempts');

        $students = $quiz->schoolClass->module->course->students;
        $attempts = $quiz->attempts;

        return view('admin.quizzes.attempts', compact('quiz', 'students', 'attempts'));
    }
}
