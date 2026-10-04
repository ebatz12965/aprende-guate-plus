<?php

namespace App\Services\Learning;

use App\AI\AiGateway;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\LearningPath;

/** Retos por hito: generación (exercise_generator) y evaluación de evidencias con rúbrica (evaluator). */
class ChallengeService
{
    public function __construct(private readonly AiGateway $gateway) {}

    public function generateForMilestone(LearningPath $path, int $milestone): array
    {
        $completed = $path->items->where('status', 'completed')->values();
        $current = $completed->last() ?? $path->items->first();
        $topics = $completed->flatMap(fn ($item) => $item->topics->pluck('title'))->filter()->values()->all();
        $fallback = [
            'title' => 'Reto práctico del '.$milestone.'%: '.($current?->title ?? $path->title),
            'area' => $current?->area ?? 'Ruta personalizada',
            'level' => $path->estimated_level ?? 'Inicial',
            'description' => 'Reto opcional para aplicar los temas que ya completaste en tu ruta: '.implode(', ', array_slice($topics, -3)).'.',
            'case_context' => 'Aplica los conocimientos de "'.($current?->title ?? $path->title).'" en una situación realista relacionada con tu objetivo: '.$path->goal.'. Explica tus decisiones y conserva evidencia de tu proceso.',
            'objectives' => ['Aplicar el contenido ya estudiado en una situación práctica.', 'Tomar decisiones justificadas según el objetivo de la ruta.', 'Comunicar el resultado y el procedimiento con claridad.'],
            'deliverables' => [['name' => 'Evidencia del reto', 'detail' => 'Adjunta un archivo o escribe una respuesta que muestre el resultado solicitado.'], ['name' => 'Explicación del proceso', 'detail' => 'Describe los pasos, decisiones y comprobaciones realizadas.']],
            'requirements' => ['Usa solo conceptos de los temas que ya completaste.', 'Explica cómo verificaste el resultado.', 'Relaciona tu propuesta directamente con la meta de aprendizaje.'],
            'rubric' => [['name' => 'Aplicación del contenido', 'weight' => 40, 'description' => 'Usa correctamente los temas ya desbloqueados.'], ['name' => 'Proceso y justificación', 'weight' => 35, 'description' => 'Explica decisiones y verificaciones de forma clara.'], ['name' => 'Comunicación del resultado', 'weight' => 25, 'description' => 'Entrega una evidencia comprensible y completa.']],
            'resources' => [],
            'estimated_minutes' => 45,
        ];
        $context = ['meta' => $path->goal, 'nivel' => $path->estimated_level, 'hito' => $milestone, 'fase_actual' => $current?->title, 'temas_ya_completados' => $topics];
        $prompt = 'Genera un único reto opcional, completo y realista en español latinoamericano para el avance indicado. Debe estar 100% relacionado con la meta y exclusivamente con los temas ya completados. No introduzcas temas de fases futuras ni retos genéricos de otra área. Incluye un caso de estudio creíble, objetivos, entregables, requisitos verificables y rúbrica cuya suma de pesos sea 100. Si requiere un archivo de apoyo, inclúyelo en resources con contenido UTF-8 en español; de lo contrario usa resources vacío. Contexto: '.json_encode($context, JSON_UNESCAPED_UNICODE);
        $result = $this->askStructured('exercise_generator', $prompt, $fallback, $this->milestoneChallengeSchema(), 'milestone_challenge', 1300, $path->user_id);

        return $this->validMilestoneChallenge($result) ? $result : $fallback;
    }

    private function milestoneChallengeSchema(): array
    {
        return ['type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'area', 'level', 'description', 'case_context', 'objectives', 'deliverables', 'requirements', 'rubric', 'resources', 'estimated_minutes'], 'properties' => ['title' => ['type' => 'string'], 'area' => ['type' => 'string'], 'level' => ['type' => 'string'], 'description' => ['type' => 'string'], 'case_context' => ['type' => 'string'], 'objectives' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 5, 'items' => ['type' => 'string']], 'deliverables' => ['type' => 'array', 'minItems' => 2, 'maxItems' => 4, 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['name', 'detail'], 'properties' => ['name' => ['type' => 'string'], 'detail' => ['type' => 'string']]]], 'requirements' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 6, 'items' => ['type' => 'string']], 'rubric' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 4, 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['name', 'weight', 'description'], 'properties' => ['name' => ['type' => 'string'], 'weight' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100], 'description' => ['type' => 'string']]]], 'resources' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['label', 'filename', 'mime', 'content'], 'properties' => ['label' => ['type' => 'string'], 'filename' => ['type' => 'string'], 'mime' => ['type' => 'string'], 'content' => ['type' => 'string']]]], 'estimated_minutes' => ['type' => 'integer', 'minimum' => 15, 'maximum' => 180]]];
    }

    private function validMilestoneChallenge(array $value): bool
    {
        return count($value['objectives'] ?? []) >= 3 && count($value['deliverables'] ?? []) >= 2 && count($value['requirements'] ?? []) >= 3 && count($value['rubric'] ?? []) >= 3 && collect($value['rubric'] ?? [])->sum('weight') === 100;
    }

    public function evaluate(Challenge $challenge, ChallengeSubmission $submission, ?string $fileExcerpt = null): array
    {
        $fallback = [
            'generated_by_ai' => false,
            'score' => null,
            'summary' => 'La evidencia quedó almacenada y está pendiente de evaluación automática.',
            'criteria' => [],
            'strengths' => [],
            'improvements' => ['Mantén una explicación clara del procedimiento y adjunta los entregables solicitados.'],
            'integrity_note' => 'La plataforma no infiere autoría; valida que la evidencia sea tuya antes de enviarla.',
        ];
        $brief = [
            'title' => $challenge->title,
            'case_context' => $challenge->case_context ?: $challenge->description,
            'objectives' => $challenge->objectives ?? [],
            'deliverables' => $challenge->deliverables ?? [],
            'requirements' => $challenge->requirements ?? [],
            'rubric' => $challenge->rubric ?? [],
            'student_response' => $submission->answer,
            'methodology' => $submission->methodology,
            'attachment' => ['name' => $submission->attachment_name, 'mime' => $submission->attachment_mime, 'size' => $submission->attachment_size, 'inspectable_excerpt' => $fileExcerpt],
        ];
        $prompt = 'Evalúa una evidencia de aprendizaje con una rúbrica. Responde exclusivamente en español latinoamericano claro. Usa únicamente los datos proporcionados; no inventes que leíste un archivo binario si no hay una vista previa textual. Puntúa cada criterio, explica de forma concreta qué se cumplió y qué falta. Datos: '.json_encode($brief, JSON_UNESCAPED_UNICODE);
        $result = $this->gateway->structured('evaluator', $prompt, $this->challengeEvaluationSchema(), 'challenge_evaluation', $submission->user_id, 850);
        if (! $result || ! $this->validChallengeEvaluation($result)) {
            return $fallback;
        }

        return array_merge($result, ['generated_by_ai' => true]);
    }

    private function challengeEvaluationSchema(): array
    {
        $list = ['type' => 'array', 'items' => ['type' => 'string']];

        return ['type' => 'object', 'additionalProperties' => false, 'required' => ['score', 'summary', 'criteria', 'strengths', 'improvements', 'integrity_note'], 'properties' => [
            'score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
            'summary' => ['type' => 'string'],
            'criteria' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['name', 'score', 'max_score', 'feedback'], 'properties' => [
                'name' => ['type' => 'string'],
                'score' => ['type' => 'integer', 'minimum' => 0],
                'max_score' => ['type' => 'integer', 'minimum' => 1],
                'feedback' => ['type' => 'string'],
            ]]],
            'strengths' => $list,
            'improvements' => $list,
            'integrity_note' => ['type' => 'string'],
        ]];
    }

    private function validChallengeEvaluation(array $value): bool
    {
        $score = $value['score'] ?? null;

        return is_int($score) && $score >= 0 && $score <= 100
            && filled($value['summary'] ?? null)
            && count($value['criteria'] ?? []) >= 1
            && collect($value['criteria'])->every(fn ($criterion) => is_int(data_get($criterion, 'score'))
                && is_int(data_get($criterion, 'max_score'))
                && data_get($criterion, 'score') >= 0
                && data_get($criterion, 'score') <= data_get($criterion, 'max_score'));
    }

    private function askStructured(string $agent, string $prompt, array $fallback, array $schema, string $name, int $maxTokens, ?int $userId = null): array
    {
        return $this->gateway->structured($agent, $prompt, $schema, $name, $userId, $maxTokens) ?? $fallback;
    }
}
