<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function index()
    {
        $levels = Level::withCount('courses')->latest()->get();
        return view('admin.levels.index', compact('levels'));
    }

    public function create()
    {
        return view('admin.levels.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:levels']);

        Level::create($request->all());

        return redirect()->route('admin.levels.index')->with('success', 'Nivel creado correctamente.');
    }

    public function edit(Level $level)
    {
        return view('admin.levels.edit', compact('level'));
    }

    public function update(Request $request, Level $level)
    {
        $request->validate(['name' => 'required|string|max:255|unique:levels,name,' . $level->id]);

        $level->update($request->all());

        return redirect()->route('admin.levels.index')->with('success', 'Nivel actualizado correctamente.');
    }

    public function destroy(Level $level)
    {
        // Opcional: Validar si el nivel tiene cursos asociados antes de eliminar.
        $level->delete();
        return redirect()->route('admin.levels.index')->with('success', 'Nivel eliminado correctamente.');
    }
}
