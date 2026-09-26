<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnnouncement extends Model
{
    protected $fillable = ['created_by', 'title', 'body', 'audience', 'send_email', 'is_published', 'published_at'];

    protected function casts(): array
    {
        return ['send_email' => 'boolean', 'is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}