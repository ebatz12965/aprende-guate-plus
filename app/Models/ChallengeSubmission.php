<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeSubmission extends Model
{
    protected $fillable = ['challenge_id', 'user_id', 'answer', 'methodology', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size', 'attachment_analysis', 'score', 'feedback', 'evaluation', 'evaluated_at', 'status'];

    protected function casts(): array
    {
        return ['evaluation' => 'array', 'evaluated_at' => 'datetime'];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
