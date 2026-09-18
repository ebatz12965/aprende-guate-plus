<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'school_classes';

    protected $fillable = [
        'module_id',
        'name',
        'content',
        'video_url',
        'source_video_path',
        'hls_path',
        'video_status',
        'video_progress',
        'video_error',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

}
