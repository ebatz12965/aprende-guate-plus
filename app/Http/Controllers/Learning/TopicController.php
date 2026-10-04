<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
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
    public function show(Request $request, int $topic): View
    {
        $path = $request->user()->activeLearningPathOrFail();
        $topic = LearningPathTopic::with(['module.learningPath', 'lesson', 'practice'])->findOrFail($topic);
        abort_unless($topic->module->learning_path_id === $path->id, 404);
        abort_if($path->hasIncompleteItemsBefore($topic->module->position), 403);

        if ($topic->status === 'pending') {
            $topic->update(['status' => 'in_progress']);
        }

        return view('adaptive.topic', compact('path', 'topic'));
    }

    public function generateLesson(Request $request, int $topic): RedirectResponse
    {
        return $this->queueContent($request, $topic, 'lesson');
    }

    public function generatePractice(Request $request, int $topic): RedirectResponse
    {
        return $this->queueContent($request, $topic, 'practice');
    }

    public function generationStatus(Request $request, int $topic): JsonResponse
    {
        $path = $request->user()->activeLearningPathOrFail();
        $topicModel = LearningPathTopic::with(['module', 'lesson', 'practice'])->findOrFail($topic);
        abort_unless($topicModel->module->learning_path_id === $path->id, 404);

        return response()->json([
            'title' => $topicModel->title,
            'lesson_status' => $topicModel->lesson ? 'completed' : $topicModel->lesson_generation_status,
            'practice_status' => $topicModel->practice ? 'completed' : $topicModel->practice_generation_status,
        ]);
    }

    public function submitPractice(Request $request, int $topic): RedirectResponse
    {
        $path = $request->user()->activeLearningPathOrFail();
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

    public function downloadPractice(Request $request, int $topic): Response
    {
        $path = $request->user()->activeLearningPathOrFail();
        $topic = LearningPathTopic::with(['module', 'practice'])->findOrFail($topic);
        abort_unless($topic->module->learning_path_id === $path->id && $topic->practice?->type === 'excel_dataset', 404);
        $artifact = $topic->practice->artifact ?? [];
        $csv = "\xEF\xBB\xBF".(string) data_get($artifact, 'content');

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.data_get($artifact, 'label', 'practica.csv').'"']);
    }

    private function queueContent(Request $request, int $topicId, string $type): RedirectResponse
    {
        $path = $request->user()->activeLearningPathOrFail();
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
}
