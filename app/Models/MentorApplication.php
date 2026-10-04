<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorApplication extends Model
{
    protected $fillable = [
        'user_id', 'status', 'professional_title', 'specialties', 'years_experience',
        'professional_background', 'teaching_approach', 'availability', 'session_duration_minutes',
        'motivation', 'questionnaire_answers', 'ai_evaluation', 'admin_notes',
        'reviewed_by', 'reviewed_at', 'test_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'questionnaire_answers' => 'array',
            'ai_evaluation' => 'array',
            'availability' => 'array',
            'reviewed_at' => 'datetime',
            'test_completed_at' => 'datetime',
            'years_experience' => 'integer',
            'session_duration_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
