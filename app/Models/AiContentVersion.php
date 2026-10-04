<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiContentVersion extends Model
{
    protected $fillable = ['content_type', 'content_id', 'version', 'payload', 'ai_request_id', 'quality_score', 'status'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
