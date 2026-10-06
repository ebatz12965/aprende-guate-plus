<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'in_progress' => 'En curso',
        'submitted' => 'Entregada',
        'evaluated' => 'Evaluada',
    ];

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

    public function isInterview(): bool
    {
        return in_array($this->type, ['hr_interview', 'technical_interview'], true);
    }
}
