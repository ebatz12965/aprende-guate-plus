<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateLearningPathJob;
use App\Jobs\GenerateTopicContentJob;
use App\Models\{AiConversation, Challenge, ChallengeSubmission, DiagnosticAttempt, LearningPath, LearningPathItemQuizAttempt, LearningPathTopic, LearningPathLesson, LearningPathPractice, LearningPathPracticeAttempt, MentorProfile, MentorshipSession, PlatformSetting, StudentProfile};
use App\Services\AiTutorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdaptiveLearningController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $path = $user->learningPaths()->where('status', 'active')->with('items.topics')->latest()->first();
        $total = $path?->items->count() ?? 0;
        $completed = $path?->items->where('status', 'completed')->count() ?? 0;

        return view('adaptive.dashboard', [
            'profile' => $user->studentProfile,
            'path' => $path,
            'total' => $total,
            'completed' => $completed,
            'progress' => $total ? (int) round($completed / $total * 100) : 0,
            'nextItem' => $path?->items->firstWhere('status', 'pending'),
            'challengeCount' => ChallengeSubmission::where('user_id', $user->id)->count(),
            'upcomingSession' => MentorshipSession::where('student_id', $user->id)->whereIn('status', ['requested', 'scheduled'])->with('mentor')->orderBy('scheduled_at')->first(),
        ]);
    }

    public function diagnostic()
    {
        return view('adaptive.diagnostic', ['settings' => PlatformSetting::aiLimits()]);
    }

    public function startDiagnostic(Request $request, AiTutorService $tutor)
    {
        $settings = PlatformSetting::aiLimits();
        $data = $request->validate(['primary_answer' => ['required', 'string', 'min:'.$settings['diagnostic_min_characters'], 'max:'.$settings['diagnostic_max_characters']]]);
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

    public function storeDiagnostic(Request $request, DiagnosticAttempt $attempt)
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status !== 'started') {
            return redirect()->route('adaptive.diagnostic')->with('info', 'Este cuestionario ya fue procesado. Puedes iniciar un nuevo diagnóstico cuando lo necesites.');
        }

        $data = $request->validate(['answers' => ['required', 'array', 'size:4'], 'answers.*' => ['required', 'integer', 'between:0,2']]);

        $attempt->update([
            'answers' => $data['answers'],
            'status' => 'generating',
            'generation_error' => null,
            'generation_requested_at' => now(),
        ]);

        GenerateLearningPathJob::dispatch($attempt->id)->onQueue('ai');

        return redirect()->route('adaptive.diagnostic.generation', $attempt)
            ->with('success', 'Estamos preparando tu ruta personalizada en segundo plano.');
    }

    public function diagnosticGeneration(Request $request, DiagnosticAttempt $attempt)
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status === 'completed') {
            return redirect()->route('adaptive.path')->with('success', 'Tu ruta personalizada está lista. Empieza con la primera fase.');
        }

        return view('adaptive.diagnostic-generating', compact('attempt'));
    }

    public function retryDiagnosticGeneration(Request $request, DiagnosticAttempt $attempt)
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);
        abort_unless($attempt->status === 'failed', 409);

        $attempt->update([
            'status' => 'generating',
            'generation_error' => null,
            'generation_requested_at' => now(),
        ]);

        GenerateLearningPathJob::dispatch($attempt->id)->onQueue('ai');

        return redirect()->route('adaptive.diagnostic.generation', $attempt)
            ->with('success', 'Reintentamos la generación de tu ruta.');
    }

    public function path(Request $request)
    {
        return view('adaptive.path', ['path' => $request->user()->learningPaths()->where('status', 'active')->with('items.topics')->latest()->first()]);
    }

    public function showPathItem(Request $request, int $item)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $item = $path->items()->with('topics.lesson', 'topics.practice')->findOrFail($item);

        abort_if($path->items()->where('position', '<', $item->position)->where('status', '!=', 'completed')->exists(), 403, 'Completa el mini-quiz anterior para desbloquear esta fase.');

        if ($item->status === 'pending') {
            $item->update(['status' => 'in_progress']);
        }

        return view('adaptive.path-item', compact('path', 'item'));
    }

    public function showTopic(Request $request, int $topic)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $topic = LearningPathTopic::with(['module.learningPath', 'lesson', 'practice'])->findOrFail($topic);
        abort_unless($topic->module->learning_path_id === $path->id, 404);
        abort_if($path->items()->where('position', '<', $topic->module->position)->where('status', '!=', 'completed')->exists(), 403);
        if ($topic->status === 'pending') $topic->update(['status' => 'in_progress']);
        return view('adaptive.topic', compact('path', 'topic'));
    }

    public function generateTopicLesson(Request $request, int $topic)
    {
        return $this->queueTopicContent($request, $topic, 'lesson');
    }

    public function generateTopicPractice(Request $request, int $topic)
    {
        return $this->queueTopicContent($request, $topic, 'practice');
    }

    private function queueTopicContent(Request $request, int $topicId, string $type)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $topic = LearningPathTopic::with($type)->findOrFail($topicId);
        abort_unless($topic->module->learning_path_id === $path->id, 404);

        $statusColumn = "{$type}_generation_status";

        if ($topic->{$type}) {
            return redirect()->route('adaptive.topic.show', $topic);
        }

        if (in_array($topic->{$statusColumn}, ['queued', 'processing'], true)) {
            return redirect()->route('adaptive.topic.show', $topic)
                ->with('info', 'La generación ya está en proceso. Esta página se actualizará automáticamente.');
        }

        $topic->update([
            $statusColumn => 'queued',
            'generation_error' => null,
            'generation_requested_at' => now(),
        ]);

        GenerateTopicContentJob::dispatch($topic->id, $type)->onQueue('ai');

        return redirect()->route('adaptive.topic.show', $topic)
            ->with('success', 'La generación comenzó en segundo plano. Puedes seguir navegando mientras se prepara el contenido.');
    }

    public function submitTopicPractice(Request $request, int $topic)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $topic = LearningPathTopic::with(['module', 'practice'])->findOrFail($topic);
        abort_unless($topic->module->learning_path_id === $path->id && $topic->practice, 404);
        $data = $request->validate(['answer' => ['required', 'string', 'max:12000']]);
        LearningPathPracticeAttempt::create(['learning_path_practice_id' => $topic->practice->id, 'user_id' => $request->user()->id, 'answer' => $data['answer'], 'submitted_at' => now()]);
        $topic->update(['status' => 'completed']);
        return back()->with('success', 'Práctica registrada. Puedes continuar con el siguiente tema o repasar esta lección.');
    }

    public function downloadTopicPractice(Request $request, int $topic)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $topic = LearningPathTopic::with(['module', 'practice'])->findOrFail($topic);
        abort_unless($topic->module->learning_path_id === $path->id && $topic->practice?->type === 'excel_dataset', 404);
        $artifact = $topic->practice->artifact ?? [];
        return response((string) data_get($artifact, 'content'), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.data_get($artifact, 'label', 'practica.csv').'"']);
    }

    public function submitPathItemQuiz(Request $request, int $item)
    {
        $user = $request->user();
        $path = $user->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $item = $path->items()->findOrFail($item);
        $data = $request->validate(['answer' => ['required', 'integer', 'between:0,2']]);
        $selectedAnswer = (int) $data['answer'];

$storedAnswer = (int) data_get($item->quiz, 'answer', -1);
        $correctAnswer = $this->knownFormulaAnswer($item->quiz) ?? $storedAnswer;

        if ($correctAnswer !== $storedAnswer) {
            $item->update(['quiz' => array_replace($item->quiz ?? [], ['answer' => $correctAnswer])]);
        }

        $isCorrect = $selectedAnswer === $correctAnswer;

        LearningPathItemQuizAttempt::create([
            'learning_path_item_id' => $item->id,
            'user_id' => $user->id,
            'selected_answer' => $selectedAnswer,
            'is_correct' => $isCorrect,
            'attempted_at' => now(),
        ]);

        if (! $isCorrect) {
            return back()->with('quiz_error', 'Aún no es la respuesta correcta. Revisa el concepto y el ejemplo e inténtalo otra vez.');
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
    private function knownFormulaAnswer(array $quiz): ?int
    {
        $question = \Illuminate\Support\Str::lower((string) data_get($quiz, 'question'));
        $formulas = [
            '=a1+b1' => ['suma', 'sumar'],
            '=a1-b1' => ['resta', 'restar'],
            '=a1*b1' => ['multiplica', 'multiplicación', 'producto'],
            '=a1/b1' => ['divide', 'división', 'reparte'],
        ];

        foreach ($formulas as $formula => $keywords) {
            if (! str_contains($question, $formula)) {
                continue;
            }

            foreach (data_get($quiz, 'options', []) as $index => $option) {
                if (\Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower((string) $option), $keywords)) {
                    return $index;
                }
            }
        }

        return null;
    }
}
