<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Jobs\EvaluatePracticeAttemptJob;
use App\Jobs\GenerateTopicContentJob;
use App\Models\LearningPathPracticeAttempt;
use App\Models\LearningPathTopic;
use App\Models\Skill;
use App\Models\SkillEvidence;
use App\Models\UserSkill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function show(LearningPathTopic $topic): View
    {
        $topic->load(['module.learningPath', 'lesson', 'practice']);
        $this->authorize('view', $topic);
        $path = $topic->module->learningPath;
        abort_if($path->hasIncompleteItemsBefore($topic->module->position), 403);

        if ($topic->status === 'pending') {
            $topic->update(['status' => 'in_progress']);
        }

        $latestAttempt = $topic->practiceAttempts()->where('user_id', $path->user_id)->latest('learning_path_practice_attempts.id')->first();

        return view('adaptive.topic', compact('path', 'topic', 'latestAttempt'));
    }

    public function generateLesson(LearningPathTopic $topic): RedirectResponse
    {
        return $this->queueContent($topic, 'lesson');
    }

    public function generatePractice(LearningPathTopic $topic): RedirectResponse
    {
        return $this->queueContent($topic, 'practice');
    }

    public function generationStatus(LearningPathTopic $topic): JsonResponse
    {
        $this->authorize('view', $topic);
        $topic->load(['lesson', 'practice']);

        return response()->json([
            'title' => $topic->title,
            'lesson_status' => $topic->lesson ? 'completed' : $topic->lesson_generation_status,
            'practice_status' => $topic->practice ? 'completed' : $topic->practice_generation_status,
        ]);
    }

    public function submitPractice(Request $request, LearningPathTopic $topic): RedirectResponse
    {
        $this->authorize('view', $topic);
        abort_unless($topic->practice, 404);
        $data = $request->validate(['answer' => ['required', 'string', 'max:12000']]);
        $attempt = LearningPathPracticeAttempt::create(['learning_path_practice_id' => $topic->practice->id, 'user_id' => $request->user()->id, 'answer' => $data['answer'], 'status' => 'queued', 'submitted_at' => now()]);
        $skill = Skill::where('slug', data_get($topic->metadata, 'skill_slug'))->first();
        if ($skill) {
            // La confianza se calcula con el puntaje real cuando el evaluador termina (EvaluatePracticeAttemptJob).
            $userSkill = UserSkill::firstOrCreate(['user_id' => $request->user()->id, 'skill_id' => $skill->id], ['level' => 0, 'confidence' => 0]);
            $userSkill->update(['level' => max(1, (int) $userSkill->level), 'last_evidenced_at' => now()]);
            SkillEvidence::create(['user_skill_id' => $userSkill->id, 'source_type' => 'learning_path_practice_attempt', 'source_id' => $attempt->id, 'summary' => 'Práctica entregada: '.$topic->title, 'metadata' => ['topic_id' => $topic->id, 'practice_type' => $topic->practice_type]]);
        }
        // La evaluación es formativa: entregar completa el tema y la retroalimentación llega después.
        $topic->update(['status' => 'completed']);
        EvaluatePracticeAttemptJob::dispatch($attempt->id)->onQueue('ai');

        return back()->with('success', 'Práctica registrada. El evaluador IA preparará tu retroalimentación; mientras tanto puedes continuar con el siguiente tema.');
    }

    public function downloadPractice(LearningPathTopic $topic): Response
    {
        $this->authorize('view', $topic);
        abort_unless($topic->practice?->type === 'excel_dataset', 404);
        $artifact = $topic->practice->artifact ?? [];
        $csv = "\xEF\xBB\xBF".(string) data_get($artifact, 'content');

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.data_get($artifact, 'label', 'practica.csv').'"']);
    }

    private function queueContent(LearningPathTopic $topic, string $type): RedirectResponse
    {
        $this->authorize('view', $topic);

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
}
