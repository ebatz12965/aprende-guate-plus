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

        $attempts = $topic->practiceAttempts()->where('user_id', $path->user_id)->latest('learning_path_practice_attempts.id')->get();
        $latestAttempt = $attempts->first();

        return view('adaptive.topic', compact('path', 'topic', 'latestAttempt', 'attempts'));
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

        return redirect()->to(route('adaptive.topic.show', $topic).'#practica')->with('success', 'Práctica enviada. El evaluador IA está revisando tu entrega; la retroalimentación aparecerá aquí en unos momentos.');
    }

    /**
     * Guarda en qué diapositiva va la persona y qué ya respondió, para continuar en cualquier dispositivo.
     * Se guarda junto al tema (cada tema pertenece a la ruta de una sola persona).
     */
    public function saveLessonProgress(Request $request, LearningPathTopic $topic): JsonResponse
    {
        $this->authorize('view', $topic);
        abort_unless($topic->lesson, 404);
        $data = $request->validate([
            'lesson_id' => ['required', 'integer'],
            'i' => ['required', 'integer', 'min:0', 'max:60'],
            'visited' => ['array', 'max:20'], 'visited.*' => ['integer', 'min:0', 'max:20'],
            'examples' => ['array', 'max:20'], 'examples.*' => ['integer', 'min:0', 'max:20'],
            'flipped' => ['array', 'max:20'], 'flipped.*' => ['integer', 'min:0', 'max:20'],
            'fixes' => ['array', 'max:20'], 'fixes.*' => ['integer', 'min:0', 'max:20'],
            'steps' => ['integer', 'min:0', 'max:20'],
            'decision' => ['nullable', 'integer', 'min:0', 'max:5'],
            'picked' => ['array', 'max:10'], 'picked.*' => ['integer', 'min:0', 'max:5'],
            'asText' => ['boolean'],
        ]);
        // Una lección regenerada no hereda el avance de la anterior.
        abort_unless((int) $data['lesson_id'] === $topic->lesson->id, 409);

        $topic->update(['metadata' => array_merge($topic->metadata ?? [], ['lesson_progress' => $data + ['saved_at' => now()->getTimestampMs()]])]);

        return response()->json(['saved' => true]);
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
