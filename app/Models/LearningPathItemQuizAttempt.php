<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathItemQuizAttempt extends Model
{
    protected $fillable = ['learning_path_item_id', 'user_id', 'selected_answer', 'is_correct', 'attempted_at'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'attempted_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LearningPathItem::class, 'learning_path_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
