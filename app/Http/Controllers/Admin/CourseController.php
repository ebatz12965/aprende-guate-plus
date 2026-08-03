<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'category'])->latest();
        if ($request->user()->hasRole('Instructor')) {
            $query->where('user_id', $request->user()->id);
        }

        return view('admin.courses.index', ['courses' => $query->get(), 'isInstructor' => $request->user()->hasRole('Instructor')]);
    }

    public function create(Request $request)
    {
        return view('admin.courses.create', [
            'categories' => Category::all(),
            'levels' => Level::all(),
            'instructors' => User::role('Instructor')->get(),
            'isInstructor' => $request->user()->hasRole('Instructor'),
        ]);
    }

    public function store(Request $request)
    {
        $isInstructor = $request->user()->hasRole('Instructor');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'level_id' => 'required|exists:levels,id',
            'user_id' => $isInstructor ? 'nullable' : 'required|exists:users,id',
        ]);

        $course = Course::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'level_id' => $validated['level_id'],
            'user_id' => $isInstructor ? $request->user()->id : $validated['user_id'],
            'status' => 'draft',
        ]);

        return redirect()->route('admin.courses.show', $course)->with('success', 'Curso creado correctamente. Ahora puedes añadir módulos.');
    }

    public function show(Request $request, Course $course)
    {
        $this->ensureOwnership($request, $course);
        $course->load(['modules.classes', 'students']);
        $potentialStudents = collect();
        if ($request->user()->hasRole('Admin')) {
            $potentialStudents = User::whereNotIn('id', $course->students->pluck('id')->push($course->user_id))->get();
        }

        return view('admin.courses.show', compact('course', 'potentialStudents'));
    }

    public function edit(Request $request, Course $course)
    {
        $this->ensureOwnership($request, $course);
        return view('admin.courses.edit', [
            'course' => $course,
            'categories' => Category::all(),
            'levels' => Level::all(),
            'instructors' => User::role('Instructor')->get(),
            'isInstructor' => $request->user()->hasRole('Instructor'),
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $this->ensureOwnership($request, $course);
        $isInstructor = $request->user()->hasRole('Instructor');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'level_id' => 'required|exists:levels,id',
            'user_id' => $isInstructor ? 'nullable' : 'required|exists:users,id',
        ]);

        $course->fill([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'level_id' => $validated['level_id'],
        ]);
        if (! $isInstructor) {
            $course->user_id = $validated['user_id'];
        }
        $course->save();

        return redirect()->route('admin.courses.show', $course)->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Request $request, Course $course)
    {
        $this->ensureOwnership($request, $course);
        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', 'Curso eliminado correctamente.');
    }

    public function toggleStatus(Request $request, Course $course)
    {
        $this->ensureOwnership($request, $course);
        $course->status = $course->status === 'draft' ? 'published' : 'draft';
        $course->save();
        return redirect()->route('admin.courses.show', $course)->with('success', $course->status === 'published' ? 'Curso publicado correctamente.' : 'Curso pasado a borrador.');
    }

    private function ensureOwnership(Request $request, Course $course): void
    {
        abort_unless($request->user()->hasRole('Admin') || $course->user_id === $request->user()->id, 403);
    }
}
