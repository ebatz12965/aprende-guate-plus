<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningPathTopic extends Model
{
    protected $fillable = [
        'learning_path_item_id', 'title', 'learning_objective', 'practice_type',
        'position', 'status', 'metadata', 'lesson_generation_status', 'practice_generation_status',
        'generation_error', 'generation_requested_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(LearningPathItem::class, 'learning_path_item_id');
    }

    public function lesson(): HasOne
    {
        return $this->hasOne(LearningPathLesson::class);
    }

    public function practice(): HasOne
    {
        return $this->hasOne(LearningPathPractice::class);
    }

    public function practiceAttempts(): HasMany
    {
        return $this->hasMany(LearningPathPracticeAttempt::class);
    }
}
