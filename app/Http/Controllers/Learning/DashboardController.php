<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\DiagnosticAttempt;
use App\Models\LearningPath;
use App\Models\MentorshipSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const MILESTONES = [25, 50, 75, 100];

    public function __invoke(Request $request): View
    {
        return view('adaptive.dashboard', $this->state($request->user()));
    }

    /**
     * Huella del estado del panel: la vista la consulta mientras algo se genera
     * y solo recarga cuando cambia, en lugar de recargar a ciegas.
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json(['key' => $this->state($request->user())['pollKey']]);
    }

    private function state(User $user): array
    {
        $path = $user->learningPaths()->whereIn('status', ['active', 'completed'])->with('items')->latest('id')->first();
        $attempt = DiagnosticAttempt::where('user_id', $user->id)->latest('id')->first();

        // Un diagnóstico sin terminar solo importa si es posterior a la ruta que se muestra.
        $pendingAttempt = $attempt && $attempt->status !== 'completed' && (! $path || $attempt->created_at->gte($path->created_at)) ? $attempt : null;

        $total = $path?->items->count() ?? 0;
        $completed = $path?->items->where('status', 'completed')->count() ?? 0;
        // Igual que el desbloqueo de retos: se redondea hacia abajo.
        $progress = $total ? (int) floor($completed / $total * 100) : 0;
        $challenge = $path && $total ? $this->challengeSummary($path, $progress, $completed, $total) : null;

        $state = [
            'stage' => $this->stage($path, $pendingAttempt, $total),
            'firstName' => strtok($user->name, ' ') ?: $user->name,
            'path' => $path,
            'pendingAttempt' => $pendingAttempt,
            'total' => $total,
            'completed' => $completed,
            'progress' => $progress,
            'milestones' => self::MILESTONES,
            'nextItem' => $path?->items->firstWhere('status', 'in_progress') ?? $path?->items->firstWhere('status', 'pending'),
            'challenge' => $challenge,
            'submittedCount' => $path ? ChallengeSubmission::where('user_id', $user->id)->whereHas('challenge', fn ($query) => $query->where('learning_path_id', $path->id))->count() : 0,
            'session' => $this->upcomingSession($user->id),
            'polling' => in_array($pendingAttempt?->status, ['preparing', 'generating'], true) || ($challenge['state'] ?? null) === 'preparing',
        ];
        $state['pollKey'] = implode('|', [$state['stage'], $pendingAttempt?->status, $challenge['state'] ?? '', $completed]);

        return $state;
    }

    private function stage(?LearningPath $path, ?DiagnosticAttempt $attempt, int $total): string
    {
        if ($path) {
            if ($path->status === 'completed') {
                return 'completed';
            }

            // Una ruta activa sin fases no tiene un siguiente paso posible.
            return $total ? 'active' : 'empty';
        }

        return match ($attempt?->status) {
            'preparing', 'started' => 'questions',
            'generating' => 'generating',
            'failed' => 'failed',
            default => 'start',
        };
    }

    /**
     * Lo siguiente que puede hacer la persona con los retos de su ruta.
     *
     * @return array{state: string, challenge?: Challenge, milestone?: int, phasesLeft?: int}
     */
    private function challengeSummary(LearningPath $path, int $progress, int $completed, int $total): array
    {
        /** @var Collection<int, Challenge> $challenges */
        $challenges = Challenge::where('user_id', $path->user_id)->where('learning_path_id', $path->id)->get()->keyBy('milestone');
        $submitted = ChallengeSubmission::where('user_id', $path->user_id)->whereIn('challenge_id', $challenges->pluck('id'))->pluck('challenge_id');
        $unlocked = collect(self::MILESTONES)->filter(fn (int $milestone) => $progress >= $milestone);

        $open = $unlocked->map(fn (int $milestone) => $challenges->get($milestone))
            ->filter(fn (?Challenge $challenge) => $challenge && ! $submitted->contains($challenge->id));

        if ($ready = $open->firstWhere('generation_status', 'completed')) {
            return ['state' => 'ready', 'challenge' => $ready];
        }
        if ($failed = $open->firstWhere('generation_status', 'failed')) {
            return ['state' => 'failed', 'milestone' => $failed->milestone];
        }
        if ($preparing = $open->first()) {
            return ['state' => 'preparing', 'milestone' => $preparing->milestone];
        }

        $next = collect(self::MILESTONES)->first(fn (int $milestone) => $progress < $milestone);
        if ($next) {
            return ['state' => 'locked', 'milestone' => $next, 'phasesLeft' => max(1, (int) ceil($next * $total / 100) - $completed)];
        }

        return ['state' => 'done'];
    }

    private function upcomingSession(int $userId): ?MentorshipSession
    {
        $sessions = MentorshipSession::where('student_id', $userId)->with('mentor');

        // Primero la próxima sesión con fecha; luego una aceptada sin fecha; al final una solicitud pendiente.
        return (clone $sessions)->whereIn('status', ['accepted', 'scheduled'])->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->first()
            ?? (clone $sessions)->whereIn('status', ['accepted', 'scheduled'])->whereNull('scheduled_at')->latest('id')->first()
            ?? (clone $sessions)->where('status', 'requested')->latest('id')->first();
    }
}
