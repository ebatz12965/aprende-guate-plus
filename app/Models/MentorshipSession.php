<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorshipSession extends Model
{
    protected $fillable = [
        'student_id',
        'mentor_id',
        'scheduled_at',
        'preferred_at',
        'duration_minutes',
        'topic',
        'status',
        'notes',
        'responded_at',
        'decision_message',
        'cancelled_by',
        'cancelled_at',
        'cancellation_message',
        'reminders_sent',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'preferred_at' => 'datetime',
            'responded_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminders_sent' => 'array',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MentorshipMessage::class)->oldest();
    }
}
