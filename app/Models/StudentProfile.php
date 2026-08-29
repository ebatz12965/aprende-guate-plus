<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'professional_goal',
        'current_level',
        'linkedin_url',
        'cv_path',
        'bio',
        'bio_visibility',
        'education',
        'education_visibility',
        'work_experience',
        'work_experience_visibility',
    ];

    protected function casts(): array
    {
        return [
            'education' => 'array',
            'work_experience' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
