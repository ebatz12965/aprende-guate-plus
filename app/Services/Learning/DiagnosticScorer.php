<?php

namespace App\Services\Learning;

/**
 * Califica el cuestionario de nivel sin IA: cada respuesta correcta suma su dificultad (1 a 3)
 * y la autoevaluación suma lo que la persona declara. El nivel sale del porcentaje logrado.
 */
class DiagnosticScorer
{
    public const LEVELS = [[25, 'Inicial'], [50, 'Básico'], [75, 'Intermedio'], [101, 'Avanzado']];

    /**
     * @return array{score: int, level: string, skills: array<int, array{name: string, score: int, level: string, correct: int, total: int, method: string}>, context: array<string, string>}
     */
    public function score(array $questions, array $answers, array $analysis = []): array
    {
        $skills = [];
        $context = [];
        foreach ($questions as $index => $question) {
            $answer = $answers[$index] ?? null;
            $kind = $question['kind'] ?? 'context';
            if ($kind === 'context') {
                if (isset($question['key'], $question['options'][$answer])) {
                    $context[$question['key']] = $question['options'][$answer];
                }

                continue;
            }
            $name = $question['skill'] ?? 'General';
            $skills[$name] ??= ['points' => 0.0, 'max' => 0.0, 'correct' => 0, 'total' => 0, 'method' => $kind];
            $weight = (int) ($question['difficulty'] ?? 1);
            $skills[$name]['total']++;
            $skills[$name]['max'] += $weight;
            if ($kind === 'knowledge' && $answer !== null && (int) $answer === (int) ($question['answer'] ?? -99)) {
                $skills[$name]['points'] += $weight;
                $skills[$name]['correct']++;
            } elseif ($kind === 'self' && is_numeric($answer) && $answer >= 0) {
                $skills[$name]['points'] += $weight * min(1, $answer / max(1, count($question['options'] ?? []) - 1));
            }
        }

        $importance = collect($analysis['skills'] ?? [])->mapWithKeys(fn ($skill) => [$skill['name'] => ($skill['importance'] ?? 'media') === 'alta' ? 2 : 1]);
        $results = collect($skills)->map(function (array $skill, string $name) {
            $score = $skill['max'] > 0 ? (int) round($skill['points'] / $skill['max'] * 100) : 0;

            return ['name' => $name, 'score' => $score, 'level' => $this->level($score), 'correct' => $skill['correct'], 'total' => $skill['total'], 'method' => $skill['method']];
        })->values();

        $weights = $results->sum(fn ($skill) => $importance[$skill['name']] ?? 1);
        $overall = $weights > 0 ? (int) round($results->sum(fn ($skill) => $skill['score'] * ($importance[$skill['name']] ?? 1)) / $weights) : 0;

        return ['score' => $overall, 'level' => $results->isEmpty() ? 'Inicial' : $this->level($overall), 'skills' => $results->all(), 'context' => $context];
    }

    public function level(int $score): string
    {
        foreach (self::LEVELS as [$limit, $level]) {
            if ($score < $limit) {
                return $level;
            }
        }

        return 'Avanzado';
    }
}
