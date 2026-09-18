<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiProvider extends Model { protected $fillable = ['slug', 'name', 'enabled', 'settings']; protected function casts(): array { return ['enabled' => 'boolean', 'settings' => 'array']; } }