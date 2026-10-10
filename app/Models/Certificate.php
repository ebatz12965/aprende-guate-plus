<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Certificado de una ruta de aprendizaje completada. Los datos impresos se copian al emitirlo.
 *
 * @property int $id
 * @property string $code
 * @property int $user_id
 * @property int|null $learning_path_id
 * @property string $recipient_name
 * @property string $title
 * @property string|null $goal
 * @property string|null $level
 * @property int|null $hours
 * @property int $phases
 * @property int $topics
 * @property string $signer_name
 * @property string $signer_title
 * @property Carbon $issued_at
 */
class Certificate extends Model
{
    protected $fillable = ['code', 'user_id', 'learning_path_id', 'recipient_name', 'title', 'goal', 'level', 'hours', 'phases', 'topics', 'signer_name', 'signer_title', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    /** En las URL se usa el código de verificación, no el id. */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<LearningPath, $this> */
    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function fileName(): string
    {
        return 'Certificado Aprende Guate - '.str($this->title)->ascii()->replaceMatches('/[^A-Za-z0-9 ]+/', '')->squish()->limit(60, '').' - '.$this->code.'.pdf';
    }
}
