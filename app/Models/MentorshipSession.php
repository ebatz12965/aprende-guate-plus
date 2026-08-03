<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MentorshipSession extends Model {
    protected $fillable = ['student_id','mentor_id','scheduled_at','topic','status','notes'];
    protected function casts(): array { return ['scheduled_at'=>'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function mentor(): BelongsTo { return $this->belongsTo(User::class, 'mentor_id'); }
}
