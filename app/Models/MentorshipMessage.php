<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorshipMessage extends Model
{
    protected $fillable = ['mentorship_session_id', 'sender_id', 'body', 'is_automatic', 'read_at'];

    protected function casts(): array
    {
        return ['is_automatic' => 'boolean', 'read_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MentorshipSession::class, 'mentorship_session_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}