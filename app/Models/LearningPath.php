<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model
{
    protected $fillable = ['user_id', 'title', 'goal', 'estimated_level', 'estimated_hours', 'generation_version', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LearningPathItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(LearningPathItem::class)->orderBy('position');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LearningPathVersion::class)->orderBy('version');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /** Una fase está bloqueada mientras alguna fase anterior no esté completada. */
    public function hasIncompleteItemsBefore(int $position): bool
    {
        return $this->items()->where('position', '<', $position)->where('status', '!=', 'completed')->exists();
    }
}
