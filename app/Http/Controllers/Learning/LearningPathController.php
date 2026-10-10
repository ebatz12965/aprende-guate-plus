<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMilestoneChallengeJob;
use App\Models\Challenge;
use App\Models\LearningPath;
use App\Models\LearningPathItemQuizAttempt;
use App\Services\Learning\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function show(Request $request): View
    {
        $path = $request->user()->learningPaths()->active()->with('items.topics')->latest()->first();
        if (! $path) {
            return view('adaptive.path', ['path' => null]);
        }

        // Resumen de cada fase para las vistas de recorrido y compacta.
        $total = max(1, $path->items->count());
        $current = $path->items->first(fn ($item) => $item->status !== 'completed');
        $phases = $path->items->values()->map(function ($item, int $index) use ($path, $current) {
            $topics = $item->topics->count();
            $done = $item->topics->where('status', 'completed')->count();

            return [
                'item' => $item,
                'index' => $index,
                'completed' => $item->status === 'completed',
                'current' => $current?->is($item) ?? false,
                'locked' => $path->items->where('position', '<', $item->position)->where('status', '!=', 'completed')->isNotEmpty(),
                'topics' => $topics,
                'topicsDone' => $done,
            ];
        });
        $completedPhases = $phases->where('completed', true)->count();
        // Los retos opcionales se desbloquean al 25, 50, 75 y 100 %: se dibujan después de la fase que alcanza cada porcentaje.
        $challenges = $path->challenges()->whereNotNull('milestone')->get()->keyBy('milestone');
        $milestones = collect([25, 50, 75, 100])->map(fn (int $percent) => [
            'percent' => $percent,
            'after' => (int) ceil($percent / 100 * $total) - 1,
            'reached' => (int) floor($completedPhases / $total * 100) >= $percent,
            'challenge' => $challenges->get($percent),
        ])->groupBy('after');

        return view('adaptive.path', [
            'path' => $path,
            'phases' => $phases,
            'currentPhase' => $phases->firstWhere('current', true),
            'completedPhases' => $completedPhases,
            'progress' => (int) floor($completedPhases / $total * 100),
            'milestones' => $milestones,
        ]);
    }

    public function showItem(Request $request, int $item): View
    {
        $path = $request->user()->activeLearningPathOrFail();
        $item = $path->items()->with('topics.lesson', 'topics.practice')->findOrFail($item);

        abort_if($path->hasIncompleteItemsBefore($item->position), 403, 'Completa el mini-quiz anterior para desbloquear esta fase.');

        if ($item->status === 'pending') {
            $item->update(['status' => 'in_progress']);
        }

        return view('adaptive.path-item', compact('path', 'item'));
    }

    public function submitQuiz(Request $request, int $item, CertificateService $certificates): RedirectResponse
    {
        $user = $request->user();
        $path = $user->activeLearningPathOrFail();
        $item = $path->items()->findOrFail($item);
        $data = $request->validate(['answer' => ['required', 'integer', 'between:0,2']]);
        $selectedAnswer = (int) $data['answer'];
        $isCorrect = $selectedAnswer === (int) data_get($item->quiz, 'answer', -1);

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
            $path->update(['status' => 'completed', 'completed_at' => now()]);
            $certificate = $certificates->issue($path);

            return redirect()->route('adaptive.certificates.show', $certificate)->with('success', '¡Ruta completada! Aquí está tu certificado. Además se desbloqueó tu reto opcional final y la ruta ya aparece como un logro en tu perfil profesional.');
        }

        return redirect()->route('adaptive.path')->with('success', '¡Mini-quiz aprobado! La siguiente fase ya está desbloqueada.');
    }

    private function queueEligibleMilestoneChallenges(LearningPath $path): void
    {
        $total = $path->items()->count();
        if ($total === 0) {
            return;
        }

        $progress = (int) floor($path->items()->where('status', 'completed')->count() / $total * 100);
        foreach ([25, 50, 75, 100] as $milestone) {
            if ($progress < $milestone) {
                continue;
            }
            $challenge = Challenge::firstOrCreate(
                ['user_id' => $path->user_id, 'learning_path_id' => $path->id, 'milestone' => $milestone],
                ['title' => 'Reto práctico del '.$milestone.'%', 'area' => 'Ruta personalizada', 'level' => $path->estimated_level ?: 'Inicial', 'description' => 'Estamos preparando un reto relacionado con tu ruta.', 'generation_status' => 'queued']
            );
            if ($challenge->wasRecentlyCreated) {
                GenerateMilestoneChallengeJob::dispatch($path->id, $milestone)->onQueue('ai');
            }
        }
    }
}
