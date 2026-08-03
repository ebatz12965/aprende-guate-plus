<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * Inscribe un usuario en un curso (para administradores).
     */
    public function store(Request $request, Course $course)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $course->students()->attach($request->user_id);

        return redirect()->route('admin.courses.show', $course)->with('success', 'Estudiante inscrito correctamente.');
    }

    /**
     * Anula la inscripción de un usuario de un curso (para administradores).
     */
    public function destroy(Course $course, User $user)
    {
        $course->students()->detach($user->id);

        return redirect()->route('admin.courses.show', $course)->with('success', 'Inscripción anulada correctamente.');
    }

    /**
     * Permite que el estudiante autenticado se inscriba en un curso.
     */
    public function enroll(Course $course)
    {
        $user = Auth::user();

        // El método syncWithoutDetaching previene duplicados si el usuario ya está inscrito.
        $user->courses()->syncWithoutDetaching($course->id);

        return redirect()->route('student.courses')->with('success', '¡Felicidades! Te has inscrito en el curso: ' . $course->title);
    }
}
