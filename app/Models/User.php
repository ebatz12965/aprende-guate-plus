<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_reminder_shown_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function assignDefaultStudentRole(): void
    {
        $role = SpatieRole::findOrCreate('Student', $this->getDefaultGuardName());
        $this->assignRole($role);
    }

    /** Agrega el rol de mentor sin retirar los roles que la persona ya tenía. */
    public function assignMentorRole(): void
    {
        $this->assignRole(SpatieRole::findOrCreate('Instructor', $this->getDefaultGuardName()));
    }

    public function learningPaths(): HasMany
    {
        return $this->hasMany(LearningPath::class);
    }

    /** Ruta activa más reciente del usuario; responde 404 si no tiene una. */
    public function activeLearningPathOrFail(): LearningPath
    {
        return $this->learningPaths()->active()->latest()->firstOrFail();
    }

    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function aiRequests(): HasMany
    {
        return $this->hasMany(AiRequest::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function mentorProfile(): HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function mentorApplications(): HasMany
    {
        return $this->hasMany(MentorApplication::class);
    }
}
