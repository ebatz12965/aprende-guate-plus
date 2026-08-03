<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Challenge extends Model {
    protected $fillable = ['created_by','title','area','level','description','instructions'];
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions(): HasMany { return $this->hasMany(ChallengeSubmission::class); }
}
