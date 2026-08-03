<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function create(Request $request, Course $course)
    {
        $this->ensureOwnership($request, $course);
        return view('admin.modules.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        $this->ensureOwnership($request, $course);
        $request->validate(['name' => 'required|string|max:255']);
        $course->modules()->create($request->only('name'));
        return redirect()->route('admin.courses.show', $course)->with('success', 'Módulo creado correctamente.');
    }

    public function show(Request $request, Module $module)
    {
        $this->ensureOwnership($request, $module->course);
        $module->load('classes');
        return view('admin.modules.show', compact('module'));
    }

    public function edit(Request $request, Module $module)
    {
        $this->ensureOwnership($request, $module->course);
        return view('admin.modules.edit', compact('module'));
    }

    public function update(Request $request, Module $module)
    {
        $this->ensureOwnership($request, $module->course);
        $request->validate(['name' => 'required|string|max:255']);
        $module->update($request->only('name'));
        return redirect()->route('admin.courses.show', $module->course)->with('success', 'Módulo actualizado correctamente.');
    }

    public function destroy(Request $request, Module $module)
    {
        $this->ensureOwnership($request, $module->course);
        $course = $module->course;
        $module->delete();
        return redirect()->route('admin.courses.show', $course)->with('success', 'Módulo eliminado correctamente.');
    }

    private function ensureOwnership(Request $request, Course $course): void
    {
        abort_unless($request->user()->hasRole('Admin') || $course->user_id === $request->user()->id, 403);
    }
}
