<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_enrollments');
    }

    public function taughtCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'user_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Obtiene los registros de progreso de clases completadas por el usuario.
     */
    public function completedClasses(): HasMany
    {
        return $this->hasMany(ClassProgress::class);
    }

    public function studentProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }
    public function assignDefaultStudentRole(): void
    {
        $role = SpatieRole::findOrCreate('Student', $this->getDefaultGuardName());

        $this->assignRole($role);
    }


    public function learningPaths(): HasMany
    {
        return $this->hasMany(LearningPath::class);
    }

    public function mentorProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }
}
