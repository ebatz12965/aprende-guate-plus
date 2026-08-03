<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::latest()->get(); // Cargar cursos, más recientes primero
        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Aquí podrías pasar categorías, niveles, etc., a la vista.
        // $categories = Category::all();
        // $levels = Level::all();
        return view('admin.courses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            // 'category_id' => 'required|exists:categories,id',
            // 'level_id' => 'required|exists:levels,id',
            // 'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $course = new Course($request->all());
        $course->slug = Str::slug($request->title); // Crear slug a partir del título
        // Lógica para asignar instructor (usuario autenticado)
        // $course->user_id = auth()->id();

        // Lógica para subir la imagen de portada
        // if ($request->hasFile('cover_image')) {
        //     $path = $request->file('cover_image')->store('course_covers', 'public');
        //     $course->cover_image_url = $path;
        // }

        $course->save();

        return redirect()->route('admin.courses.index')->with('success', 'Curso creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Curso eliminado correctamente.');
    }
}
