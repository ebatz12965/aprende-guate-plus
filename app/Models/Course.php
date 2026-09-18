<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contenedor técnico temporal para organizar recursos de video.
 * No forma parte del LMS público anterior.
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'user_id'];

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }
}