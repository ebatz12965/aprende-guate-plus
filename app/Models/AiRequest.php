<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    protected $fillable = ['request_uuid', 'user_id', 'agent_slug', 'provider', 'model', 'operation', 'input_hash', 'output_hash', 'status', 'input_tokens', 'output_tokens', 'total_tokens', 'duration_ms', 'failure_reason', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
