<?php

namespace App\AI\Contracts;

interface AiProvider
{
    /** @return array{successful: bool, content: ?string, usage: array, status: int, error: ?string} */
    public function complete(string $model, array $messages, ?array $schema, int $maxTokens, float $temperature): array;
}