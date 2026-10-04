<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentProfile extends Model
{
    // Igual que la migración: el perfil público está desactivado hasta que la persona lo active.
    protected $attributes = ['is_public' => false];

    protected $fillable = [
        'user_id',
        'is_public',
        'professional_goal',
        'current_level',
        'linkedin_url',
        'cv_path',
        'photo_path',
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
            'is_public' => 'boolean',
            'education' => 'array',
            'work_experience' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StudentProfile $profile): void {
            $profile->public_slug ??= static::newPublicSlug();
        });
    }

    /** Enlace no adivinable del perfil público (24 caracteres aleatorios, ~143 bits). */
    public static function newPublicSlug(): string
    {
        return Str::random(24);
    }

    /** Invalida el enlace anterior: quien lo tenía deja de ver el perfil. */
    public function regeneratePublicSlug(): void
    {
        $this->forceFill(['public_slug' => static::newPublicSlug()])->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
