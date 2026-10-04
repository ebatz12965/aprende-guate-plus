<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateLearningPathJob;
use App\Jobs\GenerateTopicContentJob;
use App\Jobs\GenerateMilestoneChallengeJob;
use App\Jobs\EvaluateChallengeSubmissionJob;
use App\Models\{AiConversation, Challenge, ChallengeSubmission, DiagnosticAttempt, LearningPath, LearningPathItemQuizAttempt, LearningPathTopic, LearningPathLesson, LearningPathPractice, LearningPathPracticeAttempt, MentorProfile, Skill, SkillEvidence, UserSkill, MentorshipSession, PlatformSetting, StudentProfile};
use App\Services\AiTutorService;
use Illuminate\Http\JsonResponse;
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
            'nextItem' => $path?->items->firstWhere('status', 'in_progress') ?? $path?->items->firstWhere('status', 'pending'),
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
            'questions' => $tutor->diagnosticQuestions($data['primary_answer'], $request->user()->id),
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

        $data = $request->validate(['answers' => ['required', 'array', 'size:4'], 'answers.*' => ['required', 'integer', 'between:0,3']]);

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

    public function topicGenerationStatus(Request $request, int $topic): JsonResponse
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $topicModel = LearningPathTopic::with(['module', 'lesson', 'practice'])->findOrFail($topic);
        abort_unless($topicModel->module->learning_path_id === $path->id, 404);

        return response()->json([
            'title' => $topicModel->title,
            'lesson_status' => $topicModel->lesson ? 'completed' : $topicModel->lesson_generation_status,
            'practice_status' => $topicModel->practice ? 'completed' : $topicModel->practice_generation_status,
        ]);
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
        $attempt = LearningPathPracticeAttempt::create(['learning_path_practice_id' => $topic->practice->id, 'user_id' => $request->user()->id, 'answer' => $data['answer'], 'submitted_at' => now()]);
        $skill = Skill::where('slug', data_get($topic->metadata, 'skill_slug'))->first();
        if ($skill) {
            $userSkill = UserSkill::firstOrCreate(['user_id' => $request->user()->id, 'skill_id' => $skill->id], ['level' => 0, 'confidence' => 0]);
            $userSkill->update(['level' => max(1, (int) $userSkill->level), 'confidence' => min(100, max((float) $userSkill->confidence, 25)), 'last_evidenced_at' => now()]);
            SkillEvidence::create(['user_skill_id' => $userSkill->id, 'source_type' => 'learning_path_practice_attempt', 'source_id' => $attempt->id, 'summary' => 'Práctica entregada: '.$topic->title, 'metadata' => ['topic_id' => $topic->id, 'practice_type' => $topic->practice_type]]);
        }
        $topic->update(['status' => 'completed']);
        return back()->with('success', 'Práctica registrada. Puedes continuar con el siguiente tema o repasar esta lección.');
    }

    public function downloadTopicPractice(Request $request, int $topic)
    {
        $path = $request->user()->learningPaths()->where('status', 'active')->latest()->firstOrFail();
        $topic = LearningPathTopic::with(['module', 'practice'])->findOrFail($topic);
        abort_unless($topic->module->learning_path_id === $path->id && $topic->practice?->type === 'excel_dataset', 404);
        $artifact = $topic->practice->artifact ?? [];
        $csv = "\xEF\xBB\xBF".(string) data_get($artifact, 'content');
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.data_get($artifact, 'label', 'practica.csv').'"']);
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
        $this->queueEligibleMilestoneChallenges($path);

        if (! $path->items()->where('status', '!=', 'completed')->exists()) {
            $path->update(['status' => 'completed']);

            return redirect()->route('adaptive.career')->with('success', '¡Ruta completada! Se desbloqueó tu reto opcional final y ya aparece como un logro en tu perfil profesional.');
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
        $reply = $tutor->reply($history, $request->user()->name, $request->user()->id);
        $conversation->messages()->create(['role' => 'assistant', 'content' => $reply]);

        return response()->json(['reply' => $reply]);
    }

    public function challenges(Request $request)
    {
        $user = $request->user();
        $path = $user->learningPaths()->whereIn('status', ['active', 'completed'])->with('items')->latest()->first();
        $total = $path?->items->count() ?? 0;
        $completed = $path?->items->where('status', 'completed')->count() ?? 0;
        $progress = $total ? (int) floor($completed / $total * 100) : 0;
        $challenges = $path ? Challenge::where('user_id', $user->id)->where('learning_path_id', $path->id)->orderBy('milestone')->get() : collect();

        return view('adaptive.challenges', [
            'path' => $path,
            'progress' => $progress,
            'challenges' => $challenges,
            'challengesByMilestone' => $challenges->keyBy('milestone'),
            'submissions' => ChallengeSubmission::where('user_id', $user->id)->whereIn('challenge_id', $challenges->pluck('id'))->get()->keyBy('challenge_id'),
        ]);
    }

    public function showChallenge(Request $request, Challenge $challenge)
    {
        $this->ensurePersonalChallenge($request, $challenge);
        return view('adaptive.challenge-show', [
            'challenge' => $challenge,
            'submission' => ChallengeSubmission::where('challenge_id', $challenge->id)->where('user_id', $request->user()->id)->first(),
        ]);
    }
    public function submitChallenge(Request $request, Challenge $challenge)
    {
        $this->ensurePersonalChallenge($request, $challenge);
        $data = $request->validate([
            'answer' => ['nullable', 'string', 'max:12000', 'required_without:attachment'],
            'methodology' => ['required', 'string', 'max:6000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,txt,csv,xlsx,xls,doc,docx,ppt,pptx,png,jpg,jpeg,webp,zip'],
        ]);

        $existing = ChallengeSubmission::where('challenge_id', $challenge->id)->where('user_id', $request->user()->id)->first();
        $payload = ['answer' => $data['answer'] ?? null, 'methodology' => $data['methodology'], 'status' => 'queued', 'score' => null, 'feedback' => null, 'evaluation' => null, 'evaluated_at' => null];

        if ($request->hasFile('attachment')) {
            if ($existing?->attachment_path) Storage::disk('local')->delete($existing->attachment_path);
            $file = $request->file('attachment');
            $path = $file->store('challenge-evidence/'.$request->user()->id, 'local');
            $payload = array_merge($payload, ['attachment_path' => $path, 'attachment_name' => $file->getClientOriginalName(), 'attachment_mime' => $file->getMimeType(), 'attachment_size' => $file->getSize(), 'attachment_analysis' => null]);
        }

        $submission = ChallengeSubmission::updateOrCreate(['challenge_id' => $challenge->id, 'user_id' => $request->user()->id], $payload);
        EvaluateChallengeSubmissionJob::dispatch($submission->id)->onQueue('ai');

        return redirect()->route('adaptive.challenges.show', $challenge)->with('success', 'Evidencia guardada. El agente evaluador preparará tu resultado en segundo plano.');
    }

    public function downloadChallengeResource(Request $request, Challenge $challenge, int $resource)
    {
        $this->ensurePersonalChallenge($request, $challenge);
        $item = data_get($challenge->resources ?? [], $resource);
        abort_unless(is_array($item) && filled(data_get($item, 'content')), 404);
        $mime = (string) data_get($item, 'mime', 'text/plain; charset=UTF-8');
        $content = (string) data_get($item, 'content');
        if (str_starts_with(strtolower($mime), 'text/csv')) { $content = "\xEF\xBB\xBF".$content; }
        return response($content, 200, ['Content-Type' => $mime, 'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', data_get($item, 'filename', 'material.txt')).'"']);
    }

    private function queueEligibleMilestoneChallenges(LearningPath $path): void
    {
        $total = $path->items()->count();
        if ($total === 0) return;

        $progress = (int) floor($path->items()->where('status', 'completed')->count() / $total * 100);
        foreach ([25, 50, 75, 100] as $milestone) {
            if ($progress < $milestone) continue;
            $challenge = Challenge::firstOrCreate(
                ['user_id' => $path->user_id, 'learning_path_id' => $path->id, 'milestone' => $milestone],
                ['title' => 'Reto práctico del '.$milestone.'%', 'area' => 'Ruta personalizada', 'level' => $path->estimated_level ?: 'Inicial', 'description' => 'Estamos preparando un reto relacionado con tu ruta.', 'generation_status' => 'queued']
            );
            if ($challenge->wasRecentlyCreated) GenerateMilestoneChallengeJob::dispatch($path->id, $milestone)->onQueue('ai');
        }
    }

    private function ensurePersonalChallenge(Request $request, Challenge $challenge): void
    {
        abort_unless($challenge->user_id === $request->user()->id && $challenge->learning_path_id && $challenge->generation_status === 'completed', 404);
    }
    public function downloadChallengeAttachment(Request $request, ChallengeSubmission $submission)
    {
        abort_unless($submission->user_id === $request->user()->id && $submission->attachment_path && Storage::disk('local')->exists($submission->attachment_path), 404);
        return Storage::disk('local')->download($submission->attachment_path, $submission->attachment_name);
    }
    public function mentorship(Request $request)
    {
        return view('adaptive.mentorship', ['mentors' => MentorProfile::with('user')->get(), 'sessions' => MentorshipSession::where('student_id', $request->user()->id)->with('mentor')->latest()->get()]);
    }

    public function requestMentorship(Request $request, MentorProfile $mentor)
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:160'],
            'preferred_at' => ['nullable', 'date', 'after:now'],
        ]);
        $session = MentorshipSession::create([
            'student_id' => $request->user()->id,
            'mentor_id' => $mentor->user_id,
            'topic' => $data['topic'],
            'preferred_at' => $data['preferred_at'] ?? null,
        ]);

        $mentor->user->notify(new \App\Notifications\PlatformAlertNotification(
            category: 'mentorship',
            title: 'Nueva solicitud de mentoría',
            body: $request->user()->name.' solicita tu orientación sobre “'.$session->topic.'”.',
            url: route('mentor.dashboard'),
            actionLabel: 'Revisar solicitud',
        ));

        return back()->with('success', 'Solicitud enviada al mentor. Te avisaremos cuando tenga una respuesta.');
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
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
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

        if ($request->boolean('remove_photo') && ! $request->hasFile('profile_photo') && $profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }
            $data['photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        unset($data['cv'], $data['profile_photo'], $data['remove_photo']);
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
