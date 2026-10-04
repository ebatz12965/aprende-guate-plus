<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorProfile extends Model
{
    protected $fillable = ['user_id', 'headline', 'specialties', 'about', 'acceptance_message', 'rejection_message', 'session_duration_minutes', 'availability'];

    protected function casts(): array
    {
        return ['availability' => 'array', 'session_duration_minutes' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
