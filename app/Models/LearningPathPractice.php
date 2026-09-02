<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathPractice extends Model
{
    protected $fillable = ['learning_path_topic_id', 'type', 'title', 'instructions', 'artifact', 'expected_solution', 'generated_at'];

    protected function casts(): array
    {
        return ['instructions' => 'array', 'artifact' => 'array', 'expected_solution' => 'array', 'generated_at' => 'datetime'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LearningPathTopic::class, 'learning_path_topic_id');
    }
}
