<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $key
 * @property string $status
 * @property string|null $message
 * @property Carbon|null $until
 * @property list<string>|null $preview_roles
 */
class PlatformModule extends Model
{
    protected $fillable = ['key', 'status', 'message', 'until', 'preview_roles', 'updated_by'];

    protected function casts(): array
    {
        return ['until' => 'datetime', 'preview_roles' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
