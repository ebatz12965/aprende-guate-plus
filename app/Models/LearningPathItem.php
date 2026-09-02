<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class LearningPathItem extends Model {
    protected $fillable = ['learning_path_id','title','area','description','content','quiz','position','status'];
    protected function casts(): array { return ['content'=>'array','quiz'=>'array']; }
    public function learningPath(): BelongsTo { return $this->belongsTo(LearningPath::class); }
    public function topics(): HasMany { return $this->hasMany(LearningPathTopic::class)->orderBy('position'); }
    public function quizAttempts(): HasMany { return $this->hasMany(LearningPathItemQuizAttempt::class); }
}
