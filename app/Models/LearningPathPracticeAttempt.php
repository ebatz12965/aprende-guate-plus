<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathPracticeAttempt extends Model
{
    protected $fillable = ['learning_path_practice_id', 'user_id', 'answer', 'status', 'feedback', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(LearningPathPractice::class, 'learning_path_practice_id');
    }
}
