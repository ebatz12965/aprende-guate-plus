<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathVersion extends Model
{
    protected $fillable = ['learning_path_id', 'diagnostic_attempt_id', 'version', 'architecture', 'generator'];

    protected function casts(): array
    {
        return ['architecture' => 'array'];
    }

    public function path(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function diagnosticAttempt(): BelongsTo
    {
        return $this->belongsTo(DiagnosticAttempt::class);
    }
}
