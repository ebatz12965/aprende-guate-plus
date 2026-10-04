<?php

namespace App\Services;

use App\AI\AiGateway;

/** Cuestionario de selección de mentores (agente mentor_application_assessor). */
class MentorQuestionnaireService
{
    public function __construct(private readonly AiGateway $gateway) {}

    /** @return array<int, array{key: string, title: string, prompt: string}> Siempre 20 preguntas. */
    public function generate(string $title, string $specialties, ?int $userId = null): array
    {
        // El agente recibe únicamente la especialidad y el alcance temático, nunca respuestas ni datos personales.
        $generated = $this->gateway->structured('mentor_application_assessor', 'Genera exactamente 20 preguntas abiertas, específicas y distintas entre sí, en español latinoamericano, para un examen de selección de mentor en Aprende Guate. Especialidad declarada: '.json_encode($specialties, JSON_UNESCAPED_UNICODE).'. Título del área: '.json_encode($title, JSON_UNESCAPED_UNICODE).'. Organízalas en cuatro bloques de cinco preguntas: dominio técnico aplicado, metodología de enseñanza, evaluación y retroalimentación, y ética/inclusión/logística con resolución de casos. Las preguntas deben pedir ejemplos y evidencias concretas, no respuestas de sí/no. No pidas nombres, correos ni información sensible.', [
            // El modo estricto del proveedor exige additionalProperties: false en cada objeto; sin él se rechaza el esquema.
            'type' => 'object', 'additionalProperties' => false, 'required' => ['questions'],
            'properties' => ['questions' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['key', 'title', 'prompt'], 'properties' => ['key' => ['type' => 'string'], 'title' => ['type' => 'string'], 'prompt' => ['type' => 'string']]]]],
        ], 'mentor_questionnaire_generation', $userId, 900);

        $questions = collect($generated['questions'] ?? [])->values()->map(function (array $question, int $index) {
            return ['key' => 'answer_'.($index + 1), 'title' => $question['title'] ?? 'Respuesta '.($index + 1), 'prompt' => $question['prompt'] ?? 'Explica con detalle tu respuesta.'];
        })->take(20)->values()->all();

        return count($questions) === 20 ? $questions : $this->fallbackQuestions();
    }

    /** 20 preguntas genéricas por si el agente no responde o su respuesta no es válida. */
    public function fallbackQuestions(): array
    {
        return [
            ['key' => 'answer_1', 'title' => 'Caso técnico 1', 'prompt' => 'Describe un problema real de esta especialidad y cómo lo resolverías paso a paso.'],
            ['key' => 'answer_2', 'title' => 'Caso técnico 2', 'prompt' => 'Explica una decisión técnica compleja que hayas tomado y sus consecuencias.'],
            ['key' => 'answer_3', 'title' => 'Conceptos esenciales', 'prompt' => 'Explica dos conceptos fundamentales para una persona que empieza desde cero.'],
            ['key' => 'answer_4', 'title' => 'Buenas prácticas', 'prompt' => '¿Qué errores frecuentes debe evitar alguien que está aprendiendo esta especialidad?'],
            ['key' => 'answer_5', 'title' => 'Diagnóstico inicial', 'prompt' => '¿Cómo identificarías el nivel y las necesidades de un nuevo estudiante?'],
            ['key' => 'answer_6', 'title' => 'Plan de sesión', 'prompt' => 'Describe cómo organizarías una sesión de mentoría de la duración indicada.'],
            ['key' => 'answer_7', 'title' => 'Explicación alternativa', 'prompt' => '¿Cómo explicarías de otra manera un concepto si el estudiante no lo comprende?'],
            ['key' => 'answer_8', 'title' => 'Práctica guiada', 'prompt' => 'Diseña una actividad práctica breve para que el estudiante aplique lo aprendido.'],
            ['key' => 'answer_9', 'title' => 'Retroalimentación', 'prompt' => '¿Cómo darías retroalimentación clara cuando el estudiante comete un error?'],
            ['key' => 'answer_10', 'title' => 'Evidencia de aprendizaje', 'prompt' => '¿Qué actividad o evidencia usarías para confirmar que el estudiante aprendió?'],
            ['key' => 'answer_11', 'title' => 'Criterios de logro', 'prompt' => '¿Cómo definirías criterios observables para saber que una meta fue alcanzada?'],
            ['key' => 'answer_12', 'title' => 'Seguimiento', 'prompt' => '¿Cómo documentarías el avance y decidirías el siguiente paso?'],
            ['key' => 'answer_13', 'title' => 'Límites éticos', 'prompt' => '¿Qué límites mantendrías para cuidar la privacidad, autonomía y seguridad del estudiante?'],
            ['key' => 'answer_14', 'title' => 'Cuando no sabes algo', 'prompt' => '¿Qué harías si el estudiante pregunta algo que no dominas?'],
            ['key' => 'answer_15', 'title' => 'Acompañamiento inclusivo', 'prompt' => '¿Cómo adaptarías tu comunicación a distintos niveles, ritmos y necesidades?'],
            ['key' => 'answer_16', 'title' => 'Conflicto en sesión', 'prompt' => '¿Cómo actuarías si el estudiante no está de acuerdo con tu recomendación?'],
            ['key' => 'answer_17', 'title' => 'Caso de presión', 'prompt' => '¿Cómo responderías si te piden garantizar un resultado que no puedes prometer?'],
            ['key' => 'answer_18', 'title' => 'Organización', 'prompt' => '¿Cómo gestionarías horarios, materiales y acuerdos de una mentoría?'],
            ['key' => 'answer_19', 'title' => 'Comunicación escrita', 'prompt' => 'Escribe un ejemplo de mensaje de seguimiento después de una sesión.'],
            ['key' => 'answer_20', 'title' => 'Compromiso mentor', 'prompt' => '¿Qué principios guiarán tu forma de acompañar y qué esperas aportar a la comunidad?'],
        ];
    }
}
