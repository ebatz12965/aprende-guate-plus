<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AiAgent extends Model { protected $fillable = ['slug', 'name', 'description', 'provider', 'model', 'max_tokens', 'enabled', 'settings']; protected function casts(): array { return ['enabled' => 'boolean', 'settings' => 'array']; } public function prompts(): HasMany { return $this->hasMany(AiPrompt::class); } }