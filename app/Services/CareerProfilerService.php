<?php

namespace App\Services;

use App\AI\AiGateway;
use Illuminate\Support\Str;

class CareerProfilerService
{
    public function __construct(private readonly AiGateway $ai) {}

    public function profile(string $goal, array $questions, array $answers, ?int $userId = null): array
    {
        $fallback = ['target_role' => $goal, 'current_level' => 'Inicial', 'skill_gaps' => ['Fundamentos del área', 'Práctica aplicada', 'Evidencia de habilidades'], 'recommended_duration_weeks' => 12, 'summary' => 'Perfil inicial generado a partir de tu objetivo y diagnóstico.'];
        $prompt = 'Construye un perfil profesional educativo en español. Objetivo: '.json_encode($goal, JSON_UNESCAPED_UNICODE).'. Diagnóstico: '.json_encode(['questions' => $questions, 'answers' => $answers], JSON_UNESCAPED_UNICODE).'. No inventes experiencia. Identifica brechas realistas y un nivel inicial, básico, intermedio o avanzado.';
        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['target_role', 'current_level', 'skill_gaps', 'recommended_duration_weeks', 'summary'], 'properties' => ['target_role' => ['type' => 'string'], 'current_level' => ['type' => 'string'], 'skill_gaps' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 8, 'items' => ['type' => 'string']], 'recommended_duration_weeks' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 52], 'summary' => ['type' => 'string']]];
        $result = $this->ai->structured('career_profiler', $prompt, $schema, 'career_profile', $userId, 600);
        return $this->valid($result) ? $result : $fallback;
    }

    private function valid(?array $profile): bool
    {
        return is_array($profile) && in_array(Str::lower((string) ($profile['current_level'] ?? '')), ['inicial', 'básico', 'basico', 'intermedio', 'avanzado'], true) && count($profile['skill_gaps'] ?? []) >= 3;
    }
}