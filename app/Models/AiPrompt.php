<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AiPrompt extends Model { protected $fillable = ['ai_agent_id', 'key', 'version', 'template', 'is_active']; protected function casts(): array { return ['is_active' => 'boolean']; } public function agent(): BelongsTo { return $this->belongsTo(AiAgent::class, 'ai_agent_id'); } }