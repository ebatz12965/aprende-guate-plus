<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Support\AdminAccess;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property Carbon|null $suspended_at
 * @property Carbon|null $suspended_until
 * @property string|null $suspension_reason
 * @property int|null $suspended_by
 * @property string|null $last_space
 * @property int $voice_interviews_used
 * @property bool $password_change_required
 */
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
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
            'password_change_required' => 'boolean',
            'has_local_password' => 'boolean',
            'two_factor_last_counter' => 'integer',
        ];
    }

    /** Super administrador: todos los permisos de administración, también los que no se delegan. */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(AdminAccess::SUPER_ROLE);
    }

    /**
     * Permisos de administración efectivos (el super administrador los tiene todos).
     *
     * @return list<string>
     */
    public function adminPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return [...AdminAccess::delegable(), ...array_keys(AdminAccess::SUPER_ONLY)];
        }

        return array_values(array_intersect(AdminAccess::delegable(), $this->getAllPermissions()->pluck('name')->all()));
    }

    /** Puede entrar al panel: es super administrador o tiene al menos un permiso delegado. */
    public function canAccessAdmin(): bool
    {
        return $this->adminPermissions() !== [];
    }

    /** Suspendida ahora: la suspensión temporal deja de aplicar sola cuando vence. */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null && ($this->suspended_until === null || $this->suspended_until->isFuture());
    }

    public function suspensionMessage(): string
    {
        $until = $this->suspended_until ? 'hasta el '.$this->suspended_until->format('d/m/Y H:i') : 'de forma indefinida';
        $reason = $this->suspension_reason ? ' Motivo: '.$this->suspension_reason : '';

        return "Tu cuenta está suspendida {$until}.{$reason}";
    }

    /** @param  Builder<User>  $query */
    public function scopeNotSuspended(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('suspended_at')->orWhere('suspended_until', '<=', now()));
    }

    /** @param  Builder<User>  $query */
    public function scopeSuspended(Builder $query): void
    {
        $query->whereNotNull('suspended_at')->where(fn (Builder $q) => $q->whereNull('suspended_until')->orWhere('suspended_until', '>', now()));
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
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
