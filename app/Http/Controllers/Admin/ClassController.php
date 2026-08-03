<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Jobs\ProcessVideoJob;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function create(Module $module)
    {
        $this->ensureOwnership(request(), $module);
        return view('admin.classes.create', compact('module'));
    }

    public function store(Request $request, Module $module)
    {
        $this->ensureOwnership($request, $module);
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm|max:2097152',
        ]);

        $class = new SchoolClass($request->only(['name', 'content', 'video_url']));
        $module->classes()->save($class);

        if ($request->hasFile('video_file')) {
            $class->update([
                'video_url' => null,
                'source_video_path' => $request->file('video_file')->store('uploads', 'courses'),
                'video_status' => 'queued',
                'video_progress' => 0,
                'video_error' => null,
            ]);
            ProcessVideoJob::dispatch($class->id);
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('admin.modules.show', $module), 'classId' => $class->id]);
        }

        return redirect()->route('admin.modules.show', $module)->with('success', 'Clase creada correctamente.');
    }

    public function show(SchoolClass $class)
    {
        $this->ensureOwnership(request(), $class->module);
        $class->load('tasks');
        return view('admin.classes.show', compact('class'));
    }

    /**
     * Muestra el formulario para editar una clase existente.
     */
    public function edit(SchoolClass $class)
    {
        $this->ensureOwnership(request(), $class->module);
        return view('admin.classes.edit', compact('class'));
    }

    /**
     * Actualiza una clase existente en la base de datos.
     */
    public function update(Request $request, SchoolClass $class)
    {
        $this->ensureOwnership($request, $class->module);
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm|max:2097152',
        ]);

        $class->update($request->only(['name', 'content', 'video_url']));

        if ($request->hasFile('video_file')) {
            $class->update([
                'video_url' => null,
                'source_video_path' => $request->file('video_file')->store('uploads', 'courses'),
                'hls_path' => null,
                'video_status' => 'queued',
                'video_progress' => 0,
                'video_error' => null,
            ]);
            ProcessVideoJob::dispatch($class->id);
        } elseif ($request->filled('video_url')) {
            $class->update(['hls_path' => null, 'video_status' => null, 'video_error' => null]);
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('admin.modules.show', $class->module), 'classId' => $class->id]);
        }

        return redirect()->route('admin.modules.show', $class->module)->with('success', 'Clase actualizada correctamente.');
    }

    public function destroy(SchoolClass $class)
    {
        $this->ensureOwnership(request(), $class->module);
        $module = $class->module;
        $class->delete();

        return redirect()->route('admin.modules.show', $module)->with('success', 'Clase eliminada correctamente.');
    }

    private function ensureOwnership(Request $request, Module $module): void
    {
        abort_unless($request->user()->hasRole('Admin') || $module->course->user_id === $request->user()->id, 403);
    }

    public function videoStatus(Request $request, SchoolClass $class)
    {
        $this->ensureOwnership($request, $class->module);
        return response()->json([
            'status' => $class->video_status,
            'progress' => $class->video_progress ?? 0,
            'error' => $class->video_error,
        ]);
    }
}
