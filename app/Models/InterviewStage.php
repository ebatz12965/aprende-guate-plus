<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Etapa del proceso simulado. Las entrevistas tienen un entrevistador ficticio; las pruebas no.
 *
 * @property int $id
 * @property int $interview_simulation_id
 * @property int $position
 * @property string $type
 * @property string $title
 * @property string $purpose
 * @property array{name: string, role: string, style?: string}|null $interviewer
 * @property list<string>|null $focus
 * @property int $duration_minutes
 * @property string $status
 * @property array<string, mixed>|null $content
 * @property array<string, mixed>|null $answers
 * @property array<string, mixed>|null $evaluation
 * @property int|null $score
 * @property Carbon|null $started_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $evaluated_at
 */
class InterviewStage extends Model
{
    public const TYPES = [
        'hr_interview' => 'Entrevista con Recursos Humanos',
        'reasoning_test' => 'Prueba de razonamiento y juicio situacional',
        'technical_interview' => 'Entrevista con el responsable del área',
        'practical_test' => 'Prueba práctica',
    ];

    public const STATUSES = [
        'locked' => 'Bloqueada',
        'available' => 'Disponible',
        'preparing' => 'Preparando',
        'in_progress' => 'En curso',
        'submitted' => 'Entregada',
        'evaluated' => 'Evaluada',
        'skipped' => 'Saltada',
    ];

    public const TESTS = ['reasoning_test', 'practical_test'];

    /** Estados en los que la etapa ya terminó y la siguiente se abre. */
    public const FINISHED = ['submitted', 'evaluated', 'skipped'];

    protected $fillable = ['interview_simulation_id', 'position', 'type', 'title', 'purpose', 'interviewer', 'focus', 'duration_minutes', 'content', 'answers', 'status', 'score', 'evaluation', 'started_at', 'submitted_at', 'evaluated_at'];

    protected function casts(): array
    {
        return [
            'interviewer' => 'array', 'focus' => 'array', 'content' => 'array', 'answers' => 'array', 'evaluation' => 'array',
            'started_at' => 'datetime', 'submitted_at' => 'datetime', 'evaluated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InterviewSimulation, $this> */
    public function simulation(): BelongsTo
    {
        return $this->belongsTo(InterviewSimulation::class, 'interview_simulation_id');
    }

    /** @return HasMany<InterviewTurn, $this> */
    public function turns(): HasMany
    {
        return $this->hasMany(InterviewTurn::class)->orderBy('position');
    }

    public function isTest(): bool
    {
        return in_array($this->type, self::TESTS, true);
    }

    /** Hora límite de una prueba con tiempo; null mientras no se abre. */
    public function deadline(): ?Carbon
    {
        return $this->started_at?->copy()->addMinutes($this->duration_minutes);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, self::FINISHED, true);
    }

    /**
     * Al terminar una etapa se abre la siguiente; cuando todas terminaron, la simulación queda completa
     * con el promedio de las etapas evaluadas.
     */
    public function finishAndUnlockNext(): void
    {
        $simulation = $this->simulation;
        $next = $simulation->stages()->where('position', $this->position + 1)->first();
        if ($next && $next->status === 'locked') {
            $next->update(['status' => 'available']);
        }
        $stages = $simulation->stages()->get();
        if ($stages->every(fn (InterviewStage $stage) => $stage->isFinished())) {
            $scores = $stages->whereNotNull('score')->pluck('score');
            $simulation->update(['status' => 'completed', 'completed_at' => now(), 'overall_score' => $scores->isEmpty() ? null : (int) round($scores->avg())]);
        }
    }

    public function isInterview(): bool
    {
        return in_array($this->type, ['hr_interview', 'technical_interview'], true);
    }
}
