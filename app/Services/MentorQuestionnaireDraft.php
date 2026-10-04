<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cuestionario de mentor en curso de una persona. Se guarda en caché (no en la sesión) porque lo
 * completa un job en segundo plano y debe sobrevivir varias peticiones mientras se responde.
 */
class MentorQuestionnaireDraft
{
    private const TTL_HOURS = 12;

    public static function startGenerating(int $userId, array $draft): void
    {
        Cache::put(self::key($userId), ['status' => 'generating', 'draft' => $draft, 'questions' => []], now()->addHours(self::TTL_HOURS));
    }

    public static function complete(int $userId, array $questions): void
    {
        $current = self::get($userId) ?? ['draft' => []];
        Cache::put(self::key($userId), ['status' => 'ready', 'draft' => $current['draft'], 'questions' => $questions], now()->addHours(self::TTL_HOURS));
    }

    /** @return array{status: string, draft: array, questions: array}|null */
    public static function get(int $userId): ?array
    {
        return Cache::get(self::key($userId));
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::key($userId));
    }

    private static function key(int $userId): string
    {
        return "mentor_questionnaire:{$userId}";
    }
}
