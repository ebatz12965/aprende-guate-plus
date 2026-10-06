<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $interview_stage_id
 * @property int $position
 * @property string $role
 * @property string $content
 * @property array{kind?: string, question?: int}|null $meta
 */
class InterviewTurn extends Model
{
    protected $fillable = ['interview_stage_id', 'position', 'role', 'content', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /** @return BelongsTo<InterviewStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(InterviewStage::class, 'interview_stage_id');
    }

    /** @return array{role: string, content: string, kind: ?string} */
    public function toChat(): array
    {
        return ['role' => $this->role, 'content' => $this->content, 'kind' => $this->meta['kind'] ?? null];
    }
}
