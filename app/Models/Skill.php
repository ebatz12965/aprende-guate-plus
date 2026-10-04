<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['skill_category_id', 'slug', 'name', 'description'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class, 'skill_category_id');
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'skill_dependencies', 'skill_id', 'depends_on_skill_id');
    }
}
