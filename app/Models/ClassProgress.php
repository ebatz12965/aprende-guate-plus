<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassProgress extends Model
{
    use HasFactory;

    protected $table = 'class_progress';

    // La propiedad $timestamps es true por defecto, así que no es necesario declararla.
    // Eliminamos la línea incorrecta: public $timestamps = false;

    protected $fillable = [
        'user_id',
        'school_class_id',
        'completed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
