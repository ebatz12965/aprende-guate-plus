<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use App\Models\Category;

class SearchController extends Controller
{
    public function courses()
    {
        $courses = Course::where('status', 'published')->with('instructor')->latest()->get();
        return view('public.courses', compact('courses'));
    }

    public function courseDetail(Course $course)
    {
        $course->load('instructor', 'modules.classes');
        return view('public.courseDetail', compact('course'));
    }

    public function instructors()
    {
        $instructors = User::role('Instructor')->get();
        return view('public.instructors', compact('instructors'));
    }

    public function instructorProfile(User $user)
    {
        // Asegurarse de que el usuario es un instructor antes de mostrar el perfil
        if (!$user->hasRole('Instructor')) {
            abort(404);
        }
        $user->load('taughtCourses');
        return view('public.instructorProfile', compact('user'));
    }

    public function categories()
    {
        // Lógica para mostrar categorías
    }

    public function categoryDetail($slug)
    {
        // Lógica para mostrar cursos de una categoría
    }
}
