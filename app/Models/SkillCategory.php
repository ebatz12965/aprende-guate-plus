<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillCategory extends Model
{
    protected $fillable = ['slug', 'name', 'description'];

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
