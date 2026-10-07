<?php

namespace App\Models;

use App\Support\Library\OfficeSyllabus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tema de la biblioteca compartida: su lección, práctica y extracto animado se generan una sola vez.
 *
 * @property int $id
 * @property string $course
 * @property string $slug
 * @property string $tool
 * @property string $level
 * @property int $level_order
 * @property int $position
 * @property string $title
 * @property string $learning_objective
 * @property string $practice_type
 * @property array<string, mixed>|null $lesson
 * @property array<string, mixed>|null $practice
 * @property array<string, mixed>|null $walkthrough
 * @property Carbon|null $lesson_generated_at
 * @property Carbon|null $practice_generated_at
 * @property Carbon|null $walkthrough_generated_at
 */
class LibraryTopic extends Model
{
    protected $fillable = ['course', 'slug', 'tool', 'level', 'level_order', 'position', 'title', 'learning_objective', 'practice_type', 'lesson', 'practice', 'walkthrough', 'lesson_generated_at', 'practice_generated_at', 'walkthrough_generated_at'];

    protected function casts(): array
    {
        return ['lesson' => 'array', 'practice' => 'array', 'walkthrough' => 'array', 'lesson_generated_at' => 'datetime', 'practice_generated_at' => 'datetime', 'walkthrough_generated_at' => 'datetime'];
    }

    public function toolLabel(): string
    {
        return OfficeSyllabus::TOOLS[$this->tool] ?? ucfirst($this->tool);
    }

    public function levelLabel(): string
    {
        return OfficeSyllabus::LEVELS[$this->level]['label'] ?? ucfirst($this->level);
    }

    /** Extracto animado listo para mostrar (con al menos un paso). */
    public function hasWalkthrough(): bool
    {
        return count($this->walkthrough['steps'] ?? []) > 0;
    }
}
