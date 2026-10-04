<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillEvidence extends Model
{
    // Laravel infiere "skill_evidence" (evidence no tiene plural en inglés); la migración crea skill_evidences.
    protected $table = 'skill_evidences';

    protected $fillable = ['user_skill_id', 'source_type', 'source_id', 'score', 'summary', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function userSkill(): BelongsTo
    {
        return $this->belongsTo(UserSkill::class);
    }
}
