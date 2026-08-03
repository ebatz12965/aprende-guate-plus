<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function create(SchoolClass $class)
    {
        return view('admin.tasks.create', ['class' => $class]);
    }

    public function store(Request $request, SchoolClass $class)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $task = new Task($request->all());
        $class->tasks()->save($task);

        return redirect()->route('admin.classes.show', $class)->with('success', 'Tarea creada correctamente.');
    }

    public function show(Task $task)
    {
        $task->load('schoolClass.module.course.students', 'submissions');
        $students = $task->schoolClass->module->course->students;
        return view('admin.tasks.show', compact('task', 'students'));
    }

    /**
     * Muestra el formulario para editar una tarea existente.
     */
    public function edit(Task $task)
    {
        return view('admin.tasks.edit', compact('task'));
    }

    /**
     * Actualiza una tarea existente en la base de datos.
     */
    public function update(Request $request, Task $task)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $task->update($request->all());

        return redirect()->route('admin.classes.show', $task->schoolClass)->with('success', 'Tarea actualizada correctamente.');
    }

    /**
     * Elimina una tarea de la base de datos.
     */
    public function destroy(Task $task)
    {
        $class = $task->schoolClass;
        $task->delete();

        return redirect()->route('admin.classes.show', $class)->with('success', 'Tarea eliminada correctamente.');
    }
}
