<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticAttempt extends Model
{
    protected $fillable = ['user_id', 'primary_answer', 'score', 'level', 'answers', 'questions', 'skill_results', 'status', 'generation_error', 'generation_requested_at'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'questions' => 'array', 'skill_results' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
