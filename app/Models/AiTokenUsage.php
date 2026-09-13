<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTokenUsage extends Model
{
    protected $fillable = [
        'user_id',
        'operation',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'successful',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return ['successful' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
