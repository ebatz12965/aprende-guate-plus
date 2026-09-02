<?php

namespace App\Http\Controllers;

use App\Models\{AiConversation, Challenge, ChallengeSubmission, DiagnosticAttempt, LearningPath, MentorProfile, MentorshipSession, StudentProfile};
use App\Services\AiTutorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdaptiveLearningController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $path = $user->learningPaths()->where('status', 'active')->with('items')->latest()->first();
        $total = $path?->items->count() ?? 0;
        $completed = $path?->items->where('status', 'completed')->count() ?? 0;

        return view('adaptive.dashboard', [
            'profile' => $user->studentProfile,
            'path' => $path,
            'progress' => $total ? (int) round($completed / $total * 100) : 0,
            'nextItem' => $path?->items->firstWhere('status', 'pending'),
            'challengeCount' => ChallengeSubmission::where('user_id', $user->id)->count(),
            'upcomingSession' => MentorshipSession::where('student_id', $user->id)->whereIn('status', ['requested', 'scheduled'])->with('mentor')->orderBy('scheduled_at')->first(),
        ]);
    }

    public function diagnostic()
    {
        return view('adaptive.diagnostic');
    }

    public function startDiagnostic(Request $request, AiTutorService $tutor)
    {
        $data = $request->validate(['primary_answer' => ['required', 'string', 'min:12', 'max:500']]);
        $attempt = DiagnosticAttempt::create([
            'user_id' => $request->user()->id,
            'primary_answer' => $data['primary_answer'],
            'answers' => [],
            'questions' => $tutor->diagnosticQuestions($data['primary_answer']),
        ]);

        return redirect()->route('adaptive.diagnostic.questions', $attempt);
    }

    public function diagnosticQuestions(Request $request, DiagnosticAttempt $attempt)
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status !== 'started') {
            return redirect()->route('adaptive.diagnostic')->with('info', 'Este cuestionario ya fue procesado. Puedes iniciar un nuevo diagnóstico cuando lo necesites.');
        }

        return view('adaptive.diagnostic-questions', compact('attempt'));
    }

    public function storeDiagnostic(Request $request, DiagnosticAttempt $attempt, AiTutorService $tutor)
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status !== 'started') {
            return redirect()->route('adaptive.diagnostic')->with('info', 'Este cuestionario ya fue procesado. Puedes iniciar un nuevo diagnóstico cuando lo necesites.');
        }

        $data = $request->validate(['answers' => ['required', 'array', 'size:4'], 'answers.*' => ['required', 'integer', 'between:0,2']]);

        try {
            $plan = $tutor->learningPlan($attempt->primary_answer, $attempt->questions ?? [], $data['answers']);
            $level = data_get($plan, 'level', 'Inicial');

            DB::transaction(function () use ($attempt, $data, $level, $plan, $request) {
                $attempt->update([
                    'answers' => $data['answers'],
                    'level' => $level,
                    'score' => 0,
                    'skill_results' => ['summary' => data_get($plan, 'summary')],
                    'status' => 'completed',
                ]);

                StudentProfile::updateOrCreate(
                    ['user_id' => $request->user()->id],
                    ['professional_goal' => $attempt->primary_answer, 'current_level' => $level]
                );

                LearningPath::where('user_id', $request->user()->id)
                    ->where('status', 'active')
                    ->update(['status' => 'replaced']);

                $path = LearningPath::create([
                    'user_id' => $request->user()->id,
                    'title' => data_get($plan, 'title', 'Ruta personalizada'),
                    'goal' => $attempt->primary_answer,
                    'status' => 'active',
                ]);

                foreach (data_get($plan, 'items', []) as $position => $item) {
                    $path->items()->create([
                        'title' => data_get($item, 'title', 'Fase de aprendizaje'),
                        'area' => data_get($item, 'area', 'Práctica'),
                        'description' => data_get($item, 'content.concept'),
                        'content' => data_get($item, 'content', []),
                        'quiz' => data_get($item, 'quiz', []),
                        'position' => $position + 1,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'No se pudo generar tu ruta todavía. Tus respuestas siguen disponibles para que puedas intentarlo nuevamente.');
        }

        return redirect()->route('adaptive.path')->with('success', 'Tu ruta personalizada está lista. Empieza con la primera fase.');
    }

    public function path(Request $request)
    {
        return view('adaptive.path', ['path' => $request->user()->learningPaths()->where('status', 'active')->with('items')->latest()->first()]);
    }

    public function showPathItem(Request $request, int $item)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $item = $path->items()->findOrFail($item);

        abort_if($path->items()->where('position', '<', $item->position)->where('status', '!=', 'completed')->exists(), 403, 'Completa el mini-quiz anterior para desbloquear esta fase.');

        if ($item->status === 'pending') {
            $item->update(['status' => 'in_progress']);
        }

        return view('adaptive.path-item', compact('path', 'item'));
    }

    public function submitPathItemQuiz(Request $request, int $item)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $item = $path->items()->findOrFail($item);
        $data = $request->validate(['answer' => ['required', 'integer', 'between:0,2']]);

        if ($data['answer'] !== (int) data_get($item->quiz, 'answer', -1)) {
            return back()->with('error', 'Aún no es la respuesta correcta. Revisa el concepto y el ejemplo e inténtalo otra vez.');
        }

        $item->update(['status' => 'completed']);

        if (! $path->items()->where('status', '!=', 'completed')->exists()) {
            $path->update(['status' => 'completed']);

            return redirect()->route('adaptive.career')->with('success', '¡Ruta completada! Ya aparece como un logro en tu perfil profesional.');
        }

        return redirect()->route('adaptive.path')->with('success', '¡Mini-quiz aprobado! La siguiente fase ya está desbloqueada.');
    }

    public function tutor(Request $request)
    {
        $conversation = AiConversation::firstOrCreate(['user_id' => $request->user()->id, 'title' => 'Tutor IA']);

        return view('adaptive.tutor', compact('conversation'));
    }

    public function sendTutorMessage(Request $request, AiTutorService $tutor)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:600']]);
        $conversation = AiConversation::firstOrCreate(['user_id' => $request->user()->id, 'title' => 'Tutor IA']);
        $conversation->messages()->create(['role' => 'user', 'content' => $data['message']]);
        $history = $conversation->messages()->latest()->take(6)->get()->reverse()->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])->values()->all();
        $reply = $tutor->reply($history, $request->user()->name);
        $conversation->messages()->create(['role' => 'assistant', 'content' => $reply]);

        return response()->json(['reply' => $reply]);
    }

    public function challenges(Request $request)
    {
        return view('adaptive.challenges', ['challenges' => Challenge::latest()->get(), 'submissions' => ChallengeSubmission::where('user_id', $request->user()->id)->get()->keyBy('challenge_id')]);
    }

    public function submitChallenge(Request $request, Challenge $challenge)
    {
        $data = $request->validate(['answer' => ['required', 'string', 'max:12000']]);
        ChallengeSubmission::updateOrCreate(['challenge_id' => $challenge->id, 'user_id' => $request->user()->id], ['answer' => $data['answer'], 'status' => 'submitted']);

        return back()->with('success', 'Reto enviado. Un mentor o la IA podrá darte retroalimentación.');
    }

    public function mentorship(Request $request)
    {
        return view('adaptive.mentorship', ['mentors' => MentorProfile::with('user')->get(), 'sessions' => MentorshipSession::where('student_id', $request->user()->id)->with('mentor')->latest()->get()]);
    }

    public function requestMentorship(Request $request, MentorProfile $mentor)
    {
        $data = $request->validate(['topic' => ['required', 'string', 'max:160']]);
        MentorshipSession::create(['student_id' => $request->user()->id, 'mentor_id' => $mentor->user_id, 'topic' => $data['topic']]);

        return back()->with('success', 'Solicitud enviada al mentor.');
    }

    public function career(Request $request)
    {
        $user = $request->user();

        return view('adaptive.career', [
            'profile' => StudentProfile::firstOrCreate(['user_id' => $user->id]),
            'completedPaths' => $user->learningPaths()->where('status', 'completed')->latest()->get(),
        ]);
    }

    public function updateCareer(Request $request)
    {
        $data = $request->validate([
            'professional_goal' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'bio_visibility' => ['required', 'in:public,private'],
            'education' => ['nullable', 'array', 'max:10'],
            'education.*.institution' => ['nullable', 'string', 'max:160'],
            'education.*.program' => ['nullable', 'string', 'max:160'],
            'education.*.period' => ['nullable', 'string', 'max:80'],
            'education_visibility' => ['required', 'in:public,private'],
            'work_experience' => ['nullable', 'array', 'max:10'],
            'work_experience.*.role' => ['nullable', 'string', 'max:160'],
            'work_experience.*.company' => ['nullable', 'string', 'max:160'],
            'work_experience.*.period' => ['nullable', 'string', 'max:80'],
            'work_experience.*.description' => ['nullable', 'string', 'max:800'],
            'work_experience_visibility' => ['required', 'in:public,private'],
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        foreach (['education', 'work_experience'] as $section) {
            $data[$section] = collect($data[$section] ?? [])
                ->filter(fn (array $entry) => collect($entry)->filter()->isNotEmpty())
                ->values()
                ->all();
        }

        $profile = StudentProfile::firstOrCreate(['user_id' => $request->user()->id]);

        if ($request->hasFile('cv')) {
            if ($profile->cv_path) {
                Storage::disk('public')->delete($profile->cv_path);
            }
            $data['cv_path'] = $request->file('cv')->store('cvs', 'public');
        }

        unset($data['cv']);
        $profile->update($data);

        return back()->with('success', 'Perfil profesional actualizado.');
    }

    public function publicProfile(\App\Models\User $user)
    {
        $user->load('studentProfile');
        $profile = $user->studentProfile;
        abort_unless($profile, 404);

        return view('adaptive.public-profile', [
            'user' => $user,
            'profile' => $profile,
            'completedPaths' => $user->learningPaths()->where('status', 'completed')->latest()->get(),
        ]);
    }
}
