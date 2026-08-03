<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MentorProfile extends Model {
    protected $fillable = ['user_id','headline','specialties','about'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
