<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathPracticeAttempt extends Model
{
    protected $fillable = ['learning_path_practice_id', 'user_id', 'answer', 'status', 'score', 'feedback', 'evaluation', 'submitted_at', 'evaluated_at'];

    protected function casts(): array
    {
        return ['evaluation' => 'array', 'submitted_at' => 'datetime', 'evaluated_at' => 'datetime'];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(LearningPathPractice::class, 'learning_path_practice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
