<?php

namespace App\AI;

use Illuminate\Support\Str;

/**
 * Texto de una persona usuaria que va dentro de un prompt.
 *
 * Se encierra entre <<< y >>> y se le quitan esos delimitadores para que no pueda «cerrar» el bloque
 * y escribir instrucciones fuera de él. Las instrucciones del prompt indican tratarlo como dato.
 */
class PromptData
{
    public static function clean(?string $text, int $limit): string
    {
        $text = preg_replace('/<{3,}|>{3,}/u', '', (string) $text) ?? '';
        // Una persona no puede hacerse pasar por la estructura del prompt (PREGUNTA 3:, RESPUESTA:, etc.).
        $text = preg_replace('/^(\s*)(RE)?(PREGUNTA|RESPUESTA)\b/mu', '$1«$2$3»', $text) ?? '';

        return Str::limit($text, $limit, '');
    }

    public static function fence(?string $text, int $limit): string
    {
        return "<<<\n".self::clean($text, $limit)."\n>>>";
    }
}
