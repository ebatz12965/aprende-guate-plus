<?php

namespace App\Support;

use Throwable;

/** Ayudas para las páginas de error. */
final class ErrorPage
{
    /**
     * Mensaje propio de la excepción (por ejemplo, un abort(403, '…') escrito en español para la persona),
     * o null si es uno genérico del framework en inglés, que la página reemplaza por su propio texto.
     */
    public static function customMessage(?Throwable $exception): ?string
    {
        $message = trim((string) $exception?->getMessage());
        $generic = '/^(This action is unauthorized|Unauthorized|Unauthenticated|Forbidden|Not Found|No query results|The route .* could not be found|Too Many Attempts|Service Unavailable|Server Error|CSRF token mismatch|Page Expired|Method Not Allowed|The .* method is not supported)/i';

        return $message === '' || preg_match($generic, $message) ? null : $message;
    }
}
