<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Challenge extends Model {
    protected $fillable = ['created_by','title','area','level','description','case_context','objectives','deliverables','requirements','rubric','resources','estimated_minutes','instructions'];
    protected function casts(): array { return ['objectives'=>'array','deliverables'=>'array','requirements'=>'array','rubric'=>'array','resources'=>'array']; }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions(): HasMany { return $this->hasMany(ChallengeSubmission::class); }
}