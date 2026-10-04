<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'in_app_messages',
        'email_messages',
        'in_app_mentorship',
        'email_mentorship',
        'in_app_platform',
        'email_platform',
    ];

    protected function casts(): array
    {
        return [
            'in_app_messages' => 'boolean',
            'email_messages' => 'boolean',
            'in_app_mentorship' => 'boolean',
            'email_mentorship' => 'boolean',
            'in_app_platform' => 'boolean',
            'email_platform' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(User $user): self
    {
        return static::firstOrCreate(['user_id' => $user->id]);
    }

    public function receives(string $channel, string $category): bool
    {
        return (bool) $this->{($channel === 'mail' ? 'email' : 'in_app').'_'.$category};
    }
}
