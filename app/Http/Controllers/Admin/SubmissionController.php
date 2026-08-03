<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * Muestra el formulario para calificar una entrega.
     */
    public function edit(Submission $submission)
    {
        $submission->load('task', 'student');
        return view('admin.submissions.edit', compact('submission'));
    }

    /**
     * Actualiza la calificación y el feedback de una entrega.
     */
    public function update(Request $request, Submission $submission)
    {
        $request->validate([
            'grade' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $submission->update($request->all());

        return redirect()->route('admin.tasks.show', $submission->task)->with('success', 'Calificación guardada correctamente.');
    }
}
