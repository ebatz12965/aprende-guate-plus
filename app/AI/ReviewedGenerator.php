<?php

namespace App\AI;

/**
 * Generación estructurada con control de calidad:
 * borrador → validación (con un reintento que recibe los problemas) → revisión editorial
 * (agente content_reviewer) → validación final. Un problema grave descarta un candidato;
 * uno leve se le pide corregir al revisor. Si no queda ningún candidato válido, se usa el respaldo.
 */
class ReviewedGenerator
{
    public function __construct(private readonly AiGateway $gateway) {}

    /**
     * @param  callable(array): array{0: array<int, string>, 1: array<int, string>}  $problems  Problemas graves y leves.
     * @param  callable(): array  $fallback
     * @param  string|null  $reviewInstructions  null: sin revisión editorial.
     */
    public function generate(string $agent, string $prompt, ?string $reviewInstructions, array $schema, string $operation, int $maxTokens, ?int $userId, callable $problems, callable $fallback): array
    {
        // Las piezas largas tardan más que una respuesta corta: usan su propio tiempo de espera.
        $timeout = (int) config('ai.content_timeout');
        $best = $this->candidate($this->gateway->structured($agent, $prompt, $schema, $operation, $userId, $maxTokens, $timeout), $problems);

        // Un reintento: con los problemas encontrados, o igual que antes si la respuesta llegó vacía o cortada.
        if (! $best || $best['hard'] || $best['soft']) {
            $issues = $best ? array_merge($best['hard'], $best['soft']) : [];
            $retryPrompt = $issues ? $prompt."\n".'Tu versión anterior tenía estos problemas; corrígelos: '.implode(' ', $issues) : $prompt;
            $retry = $this->candidate($this->gateway->structured($agent, $retryPrompt, $schema, $operation.'_retry', $userId, $maxTokens, $timeout), $problems);
            $best = $this->better($best, $retry);
        }

        if (! $best || $best['hard']) {
            return $fallback();
        }

        if ($reviewInstructions === null || ! config('ai.content_review', true)) {
            return $best['value'];
        }

        $reviewSchema = $schema;
        $reviewSchema['properties']['review_notes'] = ['type' => 'array', 'items' => ['type' => 'string']];
        $reviewSchema['required'][] = 'review_notes';
        $pending = $best['soft'] ? "\n".'Además corrige estos problemas detectados: '.implode(' ', $best['soft']) : '';
        $response = $this->gateway->structured('content_reviewer', $reviewInstructions.$pending."\n".'Contexto y requisitos originales: '.$prompt."\n".'Borrador a revisar: '.json_encode($best['value'], JSON_UNESCAPED_UNICODE),
            $reviewSchema, $operation.'_review', $userId, $maxTokens, $timeout);
        $reviewed = $this->candidate($response ? collect($response)->except('review_notes')->all() : null, $problems);

        // Si la revisión falla o empeora la pieza, se conserva el borrador ya validado.
        return $this->better($best, $reviewed)['value'];
    }

    /** @return array{value: array, hard: array<int, string>, soft: array<int, string>}|null */
    private function candidate(?array $value, callable $problems): ?array
    {
        if (! $value) {
            return null;
        }
        [$hard, $soft] = $problems($value);

        return ['value' => $value, 'hard' => $hard, 'soft' => $soft];
    }

    /** El candidato sin problemas graves y con menos problemas leves; ante empate, el más reciente. */
    private function better(?array $current, ?array $next): ?array
    {
        if (! $next || $next['hard']) {
            return $current;
        }
        if (! $current || $current['hard']) {
            return $next;
        }

        return count($next['soft']) <= count($current['soft']) ? $next : $current;
    }
}
