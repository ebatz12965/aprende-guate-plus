<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proceso de selección simulado para un puesto: empresa ficticia, entrevistadores y etapas.
 *
 * @property int $id
 * @property int $user_id
 * @property string $job_title
 * @property string $area
 * @property string $seniority
 * @property string|null $job_description
 * @property array<string, string>|null $company
 * @property string|null $summary
 * @property string $status
 * @property string|null $generated_by
 * @property string|null $generation_error
 */
class InterviewSimulation extends Model
{
    public const AREAS = [
        'soporte' => 'Tecnología: soporte e infraestructura',
        'desarrollo' => 'Tecnología: desarrollo de software',
        'administracion' => 'Administración y oficina',
        'contabilidad' => 'Contabilidad y finanzas',
        'ventas' => 'Ventas y atención al cliente',
        'otra' => 'Otra área',
    ];

    public const SENIORITY = [
        'primer_empleo' => 'Primer empleo o práctica',
        'junior' => 'Junior (1 a 2 años)',
        'intermedio' => 'Intermedio (3 a 5 años)',
        'experimentado' => 'Con experiencia (más de 5 años)',
    ];

    protected $fillable = ['user_id', 'job_title', 'area', 'seniority', 'job_description', 'company', 'summary', 'status', 'generated_by', 'generation_error', 'overall_score', 'report', 'completed_at'];

    protected function casts(): array
    {
        return ['company' => 'array', 'report' => 'array', 'completed_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<InterviewStage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(InterviewStage::class)->orderBy('position');
    }

    public function areaLabel(): string
    {
        return self::AREAS[$this->area] ?? $this->area;
    }

    public function seniorityLabel(): string
    {
        return self::SENIORITY[$this->seniority] ?? $this->seniority;
    }
}
