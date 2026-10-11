<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $completed_at
 */
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
        'completed_at',
        'completed_by',
        'transcript_sent_at',
        'reminders_sent',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'preferred_at' => 'datetime',
            'responded_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'transcript_sent_at' => 'datetime',
            'reminders_sent' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** Estados en los que la conversación sigue abierta. */
    public function isActive(): bool
    {
        return in_array($this->status, ['accepted', 'scheduled'], true);
    }

    /** @return HasMany<MentorshipMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(MentorshipMessage::class)->oldest();
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(MentorshipMessage::class)->latestOfMany();
    }
}
