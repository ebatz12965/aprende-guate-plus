<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
    ];

    /**
     * La pregunta a la que pertenece esta opción.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
