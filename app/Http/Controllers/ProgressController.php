<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassProgress;
use Illuminate\Support\Str;

class ProgressController extends Controller
{
    /**
     * Marca una clase como completada y comprueba si se ha ganado un certificado.
     */
    public function markAsComplete(SchoolClass $class)
    {
        $user = Auth::user();
        $course = $class->module->course;

        if (!$user->courses->contains($course)) {
            abort(403, 'No tienes permiso para marcar esta clase como completada.');
        }

        ClassProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'school_class_id' => $class->id,
            ]
        );

        // Comprobar si el curso ha sido completado
        $totalClassesCount = $course->modules->flatMap->classes->count();
        $completedClassesCount = $user->completedClasses()->whereIn('school_class_id', $course->modules->flatMap->classes->pluck('id'))->count();

        if ($totalClassesCount > 0 && $totalClassesCount === $completedClassesCount) {
            // Generar certificado si no existe ya uno
            $certificate = Certificate::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'code' => Str::uuid()->toString(), // Generar un código único
                ]
            );

            // Si el certificado se acaba de crear, mostrar un mensaje especial
            if ($certificate->wasRecentlyCreated) {
                return redirect()->route('student.course.study', $course)->with('success', '¡Felicidades! Has completado el curso y ganado tu certificado.');
            }
        }

        return redirect()->route('student.course.study', $course)->with('success', '¡Clase marcada como completada!');
    }
}
