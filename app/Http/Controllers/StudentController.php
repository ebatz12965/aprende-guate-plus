<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Task;
use App\Models\Submission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function myCourses()
    {
        $user = Auth::user();
        $courses = $user->courses()->with('instructor', 'modules.classes')->get();

        foreach ($courses as $course) {
            $totalClasses = $course->modules->flatMap->classes->count();
            $completedClasses = $user->completedClasses()->whereIn('school_class_id', $course->modules->flatMap->classes->pluck('id'))->count();

            if ($totalClasses > 0) {
                $course->progress_percentage = round(($completedClasses / $totalClasses) * 100);
            } else {
                $course->progress_percentage = 0;
            }
        }

        return view('student.courses', compact('courses'));
    }

    public function studyCourse(Course $course)
    {
        $user = Auth::user();
        if (!$user->courses->contains($course)) {
            abort(403, 'No tienes acceso a este curso.');
        }
        $course->load(['modules.classes.tasks', 'modules.classes.quizzes', 'modules.classes.progress' => function($query) use ($user) {
            $query->where('user_id', $user->id);
        }]);
        return view('student.study', compact('course'));
    }

    public function viewTask(Task $task)
    {
        $user = Auth::user();
        $course = $task->schoolClass->module->course;
        if (!$user->courses->contains($course)) {
            abort(403, 'No tienes acceso a esta tarea.');
        }
        $submission = Submission::where('user_id', $user->id)->where('task_id', $task->id)->first();
        return view('student.task', compact('task', 'submission'));
    }

    public function submitTask(Request $request, Task $task)
    {
        $user = Auth::user();
        $course = $task->schoolClass->module->course;
        if (!$user->courses->contains($course)) {
            abort(403, 'No tienes acceso a esta tarea.');
        }
        $request->validate(['content' => 'required|string']);
        Submission::updateOrCreate(['task_id' => $task->id, 'user_id' => $user->id], ['content' => $request->content]);
        return redirect()->route('student.task.view', $task)->with('success', '¡Tarea entregada correctamente!');
    }

    public function myGrades()
    {
        $user = Auth::user();
        $courses = $user->courses()->with('modules.classes.tasks')->get();

        $coursesWithGrades = [];

        foreach ($courses as $course) {
            $submissions = Submission::where('user_id', $user->id)
                ->whereIn('task_id', $course->modules->flatMap->classes->flatMap->tasks->pluck('id'))
                ->with('task')
                ->get();

            if ($submissions->isNotEmpty()) {
                $totalGrades = $submissions->whereNotNull('grade')->sum('grade');
                $gradedCount = $submissions->whereNotNull('grade')->count();
                $averageGrade = $gradedCount > 0 ? $totalGrades / $gradedCount : 0;

                $coursesWithGrades[] = [
                    'course' => $course,
                    'submissions' => $submissions,
                    'average_grade' => $averageGrade,
                ];
            }
        }

        return view('student.grades', compact('coursesWithGrades'));
    }

    public function takeQuiz(Quiz $quiz)
    {
        $user = Auth::user();
        $course = $quiz->schoolClass->module->course;

        if (!$user->courses->contains($course)) {
            abort(403, 'No tienes acceso a este cuestionario.');
        }

        $quiz->load('questions.options');
        $attempt = QuizAttempt::where('user_id', $user->id)->where('quiz_id', $quiz->id)->first();

        return view('student.quiz', compact('quiz', 'attempt'));
    }

    public function submitQuiz(Request $request, Quiz $quiz)
    {
        $user = Auth::user();
        $course = $quiz->schoolClass->module->course;

        if (!$user->courses->contains($course)) {
            abort(403, 'No tienes permiso para enviar este cuestionario.');
        }

        $request->validate(['answers' => 'required|array']);

        $correctAnswers = 0;
        $totalQuestions = $quiz->questions->count();

        foreach ($quiz->questions as $question) {
            $correctOption = $question->options->where('is_correct', true)->first();
            if ($correctOption && isset($request->answers[$question->id]) && $request->answers[$question->id] == $correctOption->id) {
                $correctAnswers++;
            }
        }

        $score = ($totalQuestions > 0) ? round(($correctAnswers / $totalQuestions) * 100) : 0;

        QuizAttempt::updateOrCreate(
            [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
            ],
            [
                'score' => $score,
            ]
        );

        return redirect()->route('student.quiz.take', $quiz)->with('success', '¡Cuestionario completado!');
    }

    /**
     * Muestra los certificados obtenidos por el estudiante.
     */
    public function myCertificates()
    {
        $user = Auth::user();
        $certificates = $user->certificates()->with('course')->latest()->get();
        return view('student.certificates', compact('certificates'));
    }
}
