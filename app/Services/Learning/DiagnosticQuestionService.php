<?php

namespace App\Services\Learning;

use App\AI\AiGateway;

/** Genera las cuatro preguntas del diagnóstico inicial (agente career_profiler). */
class DiagnosticQuestionService
{
    public function __construct(private readonly AiGateway $gateway) {}

    public function generate(string $primaryAnswer, ?int $userId = null): array
    {
        $fallback = $this->fallbackQuestions();
        $prompt = 'Crea exactamente 4 preguntas breves de diagnóstico en español latinoamericano para esta meta: "'.$primaryAnswer.'". La primera pregunta debe identificar el enfoque del usuario y usar exactamente estas 4 opciones: Impulsar mi carrera, Cambiar de rumbo, Especializarme en un tema, Crear un proyecto. Las otras 3 preguntas deben tener exactamente 3 opciones cortas y medir experiencia, disponibilidad y preferencia de aprendizaje.';
        $result = $this->askStructured($prompt, ['questions' => $fallback], $this->diagnosticSchema(), 'diagnostic_questions', 700, $userId);

        return count(data_get($result, 'questions', [])) === 4 && count(data_get($result, 'questions.0.options', [])) === 4 ? $result['questions'] : $fallback;
    }

    /** Preguntas genéricas usadas cuando el agente no responde o su respuesta no es válida. */
    public function fallbackQuestions(): array
    {
        return [
            ['question' => '¿Cuál describe mejor lo que buscas en este momento?', 'options' => ['Impulsar mi carrera', 'Cambiar de rumbo', 'Especializarme en un tema', 'Crear un proyecto']],
            ['question' => '¿Qué experiencia tienes con este objetivo?', 'options' => ['Aún no he empezado', 'He practicado un poco', 'Ya lo aplico']],
            ['question' => '¿Cuánto tiempo puedes dedicar por semana?', 'options' => ['Menos de 2 horas', '2 a 4 horas', 'Más de 4 horas']],
            ['question' => '¿Cómo aprendes mejor?', 'options' => ['Ejemplos cortos', 'Práctica paso a paso', 'Retos reales']],
        ];
    }

    private function diagnosticSchema(): array
    {
        return ['type' => 'object', 'additionalProperties' => false, 'required' => ['questions'], 'properties' => ['questions' => ['type' => 'array', 'minItems' => 4, 'maxItems' => 4, 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['question', 'options'], 'properties' => ['question' => ['type' => 'string'], 'options' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 4, 'items' => ['type' => 'string']]]]]]];
    }

    private function askStructured(string $prompt, array $fallback, array $schema, string $name, int $maxTokens, ?int $userId = null): array
    {
        return $this->gateway->structured('career_profiler', $prompt, $schema, $name, $userId, $maxTokens) ?? $fallback;
    }
}
