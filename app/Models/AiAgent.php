<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cambios hechos desde administración sobre un agente; null = valor de config/ai.php. */
class AiAgent extends Model
{
    protected $fillable = ['slug', 'enabled', 'provider', 'model', 'max_tokens', 'updated_by'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'max_tokens' => 'integer'];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
