<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathLesson extends Model
{
    protected $fillable = ['learning_path_topic_id', 'content', 'resources', 'quiz', 'generated_at'];

    protected function casts(): array
    {
        return ['content' => 'array', 'resources' => 'array', 'quiz' => 'array', 'generated_at' => 'datetime'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LearningPathTopic::class, 'learning_path_topic_id');
    }
}
