<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class UserSkill extends Model { protected $fillable = ['user_id','skill_id','level','confidence','last_evidenced_at']; protected function casts(): array { return ['last_evidenced_at'=>'datetime','confidence'=>'decimal:2']; } public function user(): BelongsTo { return $this->belongsTo(User::class); } public function skill(): BelongsTo { return $this->belongsTo(Skill::class); } public function evidences(): HasMany { return $this->hasMany(SkillEvidence::class); } }