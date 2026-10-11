<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Código corto que identifica un error del servidor. Se muestra en la página de error y queda en el registro
 * (storage/logs/laravel.log), así quien reporta el problema puede dar el código y se encuentra el detalle.
 */
final class ErrorReference
{
    private static ?string $current = null;

    public static function get(): string
    {
        // Sin letras que se confunden (0/O, 1/I) para que se pueda dictar por teléfono.
        return self::$current ??= collect(range(1, 8))->map(fn () => Str::of('23456789ABCDEFGHJKMNPQRSTUVWXYZ')->charAt(random_int(0, 30)))->implode('');
    }

    public static function reset(): void
    {
        self::$current = null;
    }
}
