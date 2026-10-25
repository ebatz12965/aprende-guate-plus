<?php

namespace App\Models;

use App\Support\Ads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PlatformSetting extends Model
{
    private const CACHE_KEY = 'platform_settings.values';

    protected $fillable = ['key', 'value'];

    public static function integer(string $key, int $default, int $minimum, int $maximum): int
    {
        $value = static::values()[$key] ?? $default;

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return $default;
        }

        return max($minimum, min($maximum, (int) $value));
    }

    public static function setInteger(string $key, int $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function setString(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function setEncrypted(string $key, string $value): void
    {
        static::setString($key, Crypt::encryptString($value));
    }

    public static function mailSettings(): array
    {
        try {
            $values = static::values();
        } catch (Throwable) {
            $values = [];
        }

        $encryptedPassword = $values['mail_password'] ?? '';
        try {
            $password = $encryptedPassword ? Crypt::decryptString($encryptedPassword) : '';
        } catch (Throwable) {
            $password = '';
        }

        return [
            'enabled' => ($values['mail_enabled'] ?? '0') === '1',
            'host' => $values['mail_host'] ?? '',
            'port' => (int) ($values['mail_port'] ?? 587),
            'encryption' => in_array(($values['mail_encryption'] ?? 'tls'), ['none', 'tls', 'ssl'], true) ? ($values['mail_encryption'] ?? 'tls') : 'tls',
            'username' => $values['mail_username'] ?? '',
            'password' => $password,
            'has_password' => $encryptedPassword !== '',
            'from_address' => $values['mail_from_address'] ?? '',
            'from_name' => $values['mail_from_name'] ?? '',
        ];
    }

    /**
     * Espacios publicitarios (Google AdSense). Apagados por defecto; los lugares permitidos están en App\Support\Ads.
     *
     * @return array{enabled: bool, client: string, slots: array<string, string>}
     */
    public static function adsSettings(): array
    {
        try {
            $values = self::values();
        } catch (Throwable) {
            $values = [];
        }

        return [
            'enabled' => ($values['ads_enabled'] ?? '0') === '1',
            'client' => (string) ($values['ads_client'] ?? ''),
            'slots' => collect(Ads::PLACEMENTS)->keys()
                ->mapWithKeys(fn (string $place) => [$place => (string) ($values['ads_slot_'.$place] ?? '')])->all(),
        ];
    }

    public static function aiTokenBudget(): int
    {
        return static::integer('ai_token_budget', 0, 0, 1_000_000_000);
    }

    public static function aiLimits(): array
    {
        return [
            'tutor_minute_limit' => static::integer('tutor_minute_limit', 6, 1, 60),
            'tutor_daily_limit' => static::integer('tutor_daily_limit', 60, 1, 5000),
            'generation_minute_limit' => static::integer('generation_minute_limit', 10, 1, 60),
            'generation_daily_limit' => static::integer('generation_daily_limit', 100, 1, 5000),
            'diagnostic_min_characters' => static::integer('diagnostic_min_characters', 12, 1, 1000),
            'diagnostic_max_characters' => static::integer('diagnostic_max_characters', 500, 10, 5000),
            'ai_token_budget' => static::aiTokenBudget(),
        ];
    }

    private static function values(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), fn () => static::query()->pluck('value', 'key')->all());
    }
}
