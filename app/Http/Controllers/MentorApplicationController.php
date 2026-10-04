<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateMentorQuestionnaireJob;
use App\Models\MentorApplication;
use App\Services\MentorQuestionnaireDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasRole('Instructor')) {
            return redirect()->route('mentor.dashboard');
        }

        $current = MentorQuestionnaireDraft::get($request->user()->id);

        return view('mentor-applications.create', [
            'application' => $request->user()->mentorApplications()->latest()->first(),
            'questionnaire' => $current['questions'] ?? [],
            'draft' => $current['draft'] ?? [],
            'generating' => ($current['status'] ?? null) === 'generating',
        ]);
    }

    public function generateQuestionnaire(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasRole('Instructor'), 403);
        if ($this->hasPendingApplication($request)) {
            return $this->pendingApplicationResponse();
        }
        $data = $request->validate([
            'professional_title' => ['required', 'string', 'max:160'],
            'specialties' => ['required', 'string', 'max:500'],
        ]);

        // Se genera en la cola; la página espera con recarga automática.
        MentorQuestionnaireDraft::startGenerating($request->user()->id, $data);
        GenerateMentorQuestionnaireJob::dispatch($request->user()->id, $data['professional_title'], $data['specialties'])->onQueue('ai');

        return redirect()->route('mentor-application.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasRole('Instructor'), 403);
        if ($this->hasPendingApplication($request)) {
            return $this->pendingApplicationResponse();
        }
        $current = MentorQuestionnaireDraft::get($request->user()->id);
        $questions = ($current['status'] ?? null) === 'ready' ? $current['questions'] : [];
        abort_if(count($questions) < 6, 422, 'Primero genera el cuestionario de mentoría.');
        $data = $request->validate([
            'professional_title' => ['required', 'string', 'max:160'], 'specialties' => ['required', 'string', 'max:500'],
            'answers' => ['required', 'array'],
        ]);
        $answers = collect($questions)->mapWithKeys(fn (array $question) => [$question['key'] => ['question' => $question['prompt'], 'answer' => (string) ($data['answers'][$question['key']] ?? '')]])->all();
        foreach ($answers as $answer) {
            if (mb_strlen(trim($answer['answer'])) < 50) {
                return back()->withInput()->withErrors(['answers' => 'Responde cada pregunta con al menos 50 caracteres y suficiente detalle.']);
            }
        }

        $answerValues = collect($answers)->pluck('answer')->values();
        MentorApplication::create([
            'user_id' => $request->user()->id,
            'status' => 'pending_review',
            'professional_title' => $data['professional_title'],
            'specialties' => $data['specialties'],
            'years_experience' => 0,
            'professional_background' => $answerValues->get(0, 'Información incluida en el cuestionario de selección.'),
            'teaching_approach' => $answerValues->get(5, 'Información incluida en el cuestionario de selección.'),
            'session_duration_minutes' => 30,
            'motivation' => $answerValues->last() ?: 'Información incluida en el cuestionario de selección.',
            'questionnaire_answers' => $answers,
            'test_completed_at' => now(),
        ]);
        MentorQuestionnaireDraft::forget($request->user()->id);

        return redirect()->route('mentor-application.create')->with('success', 'El cuestionario fue completado y tu solicitud quedó pendiente de revisión administrativa.');
    }

    private function hasPendingApplication(Request $request): bool
    {
        return $request->user()->mentorApplications()->where('status', 'pending_review')->exists();
    }

    private function pendingApplicationResponse(): RedirectResponse
    {
        return redirect()->route('mentor-application.create')->with('error', 'Ya tienes una solicitud en revisión. Espera la respuesta del administrador antes de enviar otra.');
    }
}
