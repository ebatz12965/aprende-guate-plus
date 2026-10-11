<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Versión personalizada de una plantilla de correo; sin fila, se usa el texto original de App\Support\EmailTemplates. */
class EmailTemplate extends Model
{
    protected $fillable = ['key', 'subject', 'heading', 'body', 'action_label', 'closing', 'updated_by'];

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
