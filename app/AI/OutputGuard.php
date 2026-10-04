<?php

namespace App\AI;

class OutputGuard
{
    public function decodeStructured(?string $content): ?array
    {
        if (! is_string($content) || $content === '') {
            return null;
        }
        $decoded = json_decode(trim($content), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function sanitizeText(?string $content): ?string
    {
        if (! is_string($content) || trim($content) === '') {
            return null;
        }
        $content = preg_replace('/\b(sk-[A-Za-z0-9_-]{20,}|AIza[\w-]{20,}|Bearer\s+[A-Za-z0-9._-]{20,})\b/i', '[dato sensible oculto]', $content);

        return trim(strip_tags((string) $content));
    }
}
