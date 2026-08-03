<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ChallengeSubmission extends Model {
    protected $fillable = ['challenge_id','user_id','answer','score','feedback','status'];
    public function challenge(): BelongsTo { return $this->belongsTo(Challenge::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
