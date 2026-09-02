<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class LearningPath extends Model {
    protected $fillable = ['user_id','title','goal','estimated_level','estimated_hours','generation_version','status'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(LearningPathItem::class)->orderBy('position'); }
}
