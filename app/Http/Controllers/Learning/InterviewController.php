<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Jobs\EvaluateInterviewStageJob;
use App\Jobs\GenerateInterviewPlanJob;
use App\Jobs\GenerateInterviewQuestionsJob;
use App\Models\DiagnosticAttempt;
use App\Models\InterviewSimulation;
use App\Models\InterviewStage;
use App\Services\Interviews\InterviewConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Simulador de postulación: crear un proceso de selección ficticio y recorrer sus etapas. */
class InterviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('interviews.index', [
            'simulations' => InterviewSimulation::where('user_id', $user->id)->withCount([
                'stages', 'stages as finished_stages_count' => fn ($query) => $query->whereIn('status', InterviewStage::FINISHED),
            ])->latest()->get(),
            'suggestedTitle' => $this->suggestedTitle($user->id),
            'remainingToday' => $this->remainingToday($user->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'job_title' => ['required', 'string', 'min:3', 'max:160'],
            'area' => ['required', Rule::in(array_keys(InterviewSimulation::AREAS))],
            'seniority' => ['required', Rule::in(array_keys(InterviewSimulation::SENIORITY))],
            'job_description' => ['nullable', 'string', 'max:6000'],
        ]);
        $userId = $request->user()->id;

        if ($this->remainingToday($userId) <= 0) {
            return back()->withInput()->with('error', 'Ya creaste las simulaciones disponibles por hoy. Mañana puedes crear otra; mientras tanto, continúa las que tienes.');
        }
        if (InterviewSimulation::where('user_id', $userId)->where('status', 'generating')->where('created_at', '>', now()->subMinutes(10))->exists()) {
            return back()->withInput()->with('error', 'Ya estamos preparando otra simulación. Espera a que termine para crear una nueva.');
        }

        $simulation = InterviewSimulation::create($data + ['user_id' => $userId, 'status' => 'generating']);
        GenerateInterviewPlanJob::dispatch($simulation->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.show', $simulation);
    }

    public function show(InterviewSimulation $simulation): View
    {
        $this->authorize('view', $simulation);
        $simulation->load('stages');

        return view('interviews.show', ['simulation' => $simulation]);
    }

    /** Estado para la pantalla de espera mientras se prepara el plan. */
    public function status(InterviewSimulation $simulation): JsonResponse
    {
        $this->authorize('view', $simulation);

        return response()->json(['status' => $simulation->status]);
    }

    public function retry(InterviewSimulation $simulation): RedirectResponse
    {
        $this->authorize('view', $simulation);
        abort_unless($simulation->status === 'failed', 422);
        $simulation->update(['status' => 'generating', 'generation_error' => null]);
        GenerateInterviewPlanJob::dispatch($simulation->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.show', $simulation);
    }

    public function stage(InterviewSimulation $simulation, InterviewStage $stage): View|RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        if ($stage->status === 'locked') {
            return redirect()->route('adaptive.interviews.show', $simulation)->with('info', 'Completa primero la etapa anterior.');
        }
        $next = $simulation->stages()->where('position', $stage->position + 1)->first();

        return view('interviews.stage', [
            'simulation' => $simulation,
            'stage' => $stage,
            'turns' => $stage->turns->map(fn ($turn) => $turn->toChat())->values(),
            'next' => $next && $next->status !== 'locked' ? $next : null,
        ]);
    }

    /** Comienza una entrevista: se prepara su banco de preguntas en la cola. */
    public function start(InterviewSimulation $simulation, InterviewStage $stage): RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        abort_unless($stage->isPlayable() && $stage->status === 'available', 422);
        $stage->update(['status' => 'preparing']);
        GenerateInterviewQuestionsJob::dispatch($stage->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.stage', [$simulation, $stage]);
    }

    /** Estado para las pantallas de espera (preparando preguntas o evaluando). */
    public function stageStatus(InterviewSimulation $simulation, InterviewStage $stage): JsonResponse
    {
        $this->ensureStage($simulation, $stage);

        return response()->json(['status' => $stage->status, 'evaluation' => $stage->content['evaluation_status'] ?? null]);
    }

    public function answer(Request $request, InterviewSimulation $simulation, InterviewStage $stage, InterviewConversation $conversation): JsonResponse
    {
        $this->ensureStage($simulation, $stage);
        $data = $request->validate(['answer' => ['required', 'string', 'min:2', 'max:'.InterviewConversation::MAX_ANSWER]]);
        $turns = $conversation->answer($stage, $data['answer']);
        $stage->refresh();

        return response()->json([
            'turns' => $turns,
            'status' => $stage->status,
            'question' => ($stage->content['current'] ?? 0) + 1,
            'total' => count($stage->content['questions'] ?? []),
        ]);
    }

    public function retryEvaluation(InterviewSimulation $simulation, InterviewStage $stage): RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        abort_unless($stage->status === 'submitted' && ($stage->content['evaluation_status'] ?? null) === 'failed', 422);
        $stage->update(['content' => ['evaluation_status' => 'queued'] + $stage->content]);
        EvaluateInterviewStageJob::dispatch($stage->id)->onQueue('ai');

        return redirect()->route('adaptive.interviews.stage', [$simulation, $stage]);
    }

    /** Las pruebas que todavía no existen se pueden saltar para seguir con el proceso. */
    public function skip(InterviewSimulation $simulation, InterviewStage $stage): RedirectResponse
    {
        $this->ensureStage($simulation, $stage);
        abort_unless(! $stage->isPlayable() && $stage->status === 'available', 422);
        $stage->update(['status' => 'skipped']);
        $stage->finishAndUnlockNext();
        $next = $simulation->stages()->where('position', $stage->position + 1)->first();

        return $next
            ? redirect()->route('adaptive.interviews.stage', [$simulation, $next])
            : redirect()->route('adaptive.interviews.show', $simulation);
    }

    private function ensureStage(InterviewSimulation $simulation, InterviewStage $stage): void
    {
        $this->authorize('view', $simulation);
        abort_unless($stage->interview_simulation_id === $simulation->id, 404);
    }

    public function destroy(InterviewSimulation $simulation): RedirectResponse
    {
        $this->authorize('delete', $simulation);
        $simulation->delete();

        return redirect()->route('adaptive.interviews')->with('success', 'La simulación y tus respuestas fueron eliminadas.');
    }

    private function remainingToday(int $userId): int
    {
        $used = InterviewSimulation::where('user_id', $userId)->where('created_at', '>=', now()->startOfDay())->count();

        return max(0, (int) config('ai.interview_daily_limit') - $used);
    }

    /** Puesto que la persona escribió como meta en su diagnóstico, para no tener que escribirlo otra vez. */
    private function suggestedTitle(int $userId): string
    {
        $attempt = DiagnosticAttempt::where('user_id', $userId)->whereNotNull('analysis')->latest()->first();

        return (string) (data_get($attempt?->analysis, 'target_role') ?: '');
    }
}
