<?php

namespace App\Services\Learning;

use App\AI\ReviewedGenerator;
use Illuminate\Support\Str;

/**
 * Cuestionario de nivel (agente diagnostic_designer): preguntas con respuesta correcta por
 * habilidad y dificultad, construidas desde el análisis de la meta, más dos preguntas de contexto.
 *
 * Tipos de pregunta: knowledge (tiene respuesta correcta y se califica), self (autoevaluación
 * de respaldo, se califica con menos peso) y context (no se califica; orienta la ruta).
 * La respuesta correcta nunca se envía al navegador.
 */
class DiagnosticQuestionService
{
    public const DONT_KNOW = -1;

    public function __construct(private readonly ReviewedGenerator $generator) {}

    public function generate(string $goal, array $analysis, ?int $userId = null): array
    {
        $skills = collect($analysis['skills'] ?? [])->map(fn ($skill) => ['name' => $skill['name'], 'importance' => $skill['importance'] ?? 'media', 'subskills' => $skill['subskills'] ?? []])->values()->all();
        $count = count($skills) >= 5 ? 2 : 3;

        $prompt = 'Eres evaluador experto en '.$analysis['target_role'].' y diseñas exámenes de diagnóstico. Crea un cuestionario para ubicar el nivel real de una persona en cada habilidad que exige su meta.'."\n"
            .'Meta de la persona: '.json_encode($goal, JSON_UNESCAPED_UNICODE).'. Lo que entendimos: '.$analysis['summary']."\n"
            .'Habilidades a evaluar: '.json_encode($skills, JSON_UNESCAPED_UNICODE)."\n"
            .'Requisitos: exactamente '.$count.' preguntas por habilidad (skill es el nombre exacto de la habilidad), con dificultad creciente: '.($count === 3 ? '1 (básica), 2 (intermedia) y 3 (avanzada)' : '1 (básica) y 2 o 3 (intermedia o avanzada)').'. '
            .'Cada pregunta mide una subhabilidad con una situación concreta de trabajo en Guatemala: unos datos, un fragmento de código, una frase o un caso, y pide decidir, calcular o predecir; evita preguntas de definición. '
            .'Cuatro opciones plausibles; los distractores son errores que alguien con menos nivel cometería de verdad; solo una es correcta; answer es su índice (0 a 3) y varía su posición entre preguntas. '
            .'No pongas pistas en la redacción: la opción correcta no debe ser la más larga ni la única que repite palabras de la pregunta. '
            .'explanation dice por qué la correcta lo es (no se muestra durante el cuestionario). '
            .'Las preguntas básicas las responde alguien que empieza; las avanzadas solo alguien con experiencia real. '
            .'Español latinoamericano claro con tuteo, sin Markdown; si incluyes código o datos, que quepan en pocas líneas.';

        $review = 'Revisa este cuestionario de diagnóstico como especialista en la materia. Para cada pregunta resuélvela tú mismo y confirma que la opción marcada en answer es la única correcta; si no, corrige answer o las opciones. Verifica que la dificultad indicada sea realista, que no haya pistas en la redacción, que los cálculos y el código sean correctos y que cada habilidad tenga sus '.$count.' preguntas. Devuelve el cuestionario completo.';

        $generated = $this->generator->generate('diagnostic_designer', $prompt, $review, $this->schema($count * count($skills)), 'diagnostic_questions', (int) config('ai.diagnostic_max_tokens', 4000), $userId,
            fn (array $value) => $this->problems($value, $skills, $count), fn () => ['questions' => [], 'fallback' => true]);

        $knowledge = collect($generated['questions'] ?? [])->map(fn ($question) => [
            'kind' => 'knowledge',
            'skill' => $this->matchSkill((string) $question['skill'], $skills),
            'difficulty' => (int) $question['difficulty'],
            'question' => $question['question'],
            'options' => array_values($question['options']),
            'answer' => (int) $question['answer'],
            'explanation' => $question['explanation'] ?? '',
        ])->sortBy([['skill', 'asc'], ['difficulty', 'asc']]);

        // Orden: por habilidad en el orden del análisis y de lo básico a lo avanzado.
        $order = array_flip(array_column($skills, 'name'));
        $knowledge = $knowledge->sortBy(fn ($question) => ($order[$question['skill']] ?? 99) * 10 + $question['difficulty'])->values()->all();

        $questions = $knowledge ?: $this->selfAssessment($skills);

        return array_merge($this->contextQuestions(), $questions);
    }

    /** Preguntas fijas que orientan la ruta; no se califican. */
    public function contextQuestions(): array
    {
        return [
            ['kind' => 'context', 'key' => 'weekly_time', 'question' => '¿Cuánto tiempo puedes dedicar a aprender cada semana?', 'options' => ['Menos de 2 horas', 'De 2 a 4 horas', 'De 5 a 8 horas', 'Más de 8 horas']],
            ['kind' => 'context', 'key' => 'experience', 'question' => '¿Qué experiencia práctica tienes con lo que quieres aprender?', 'options' => ['Ninguna todavía', 'Lo he usado en estudios o cursos', 'Lo uso en mi trabajo', 'Lo domino y podría enseñarlo']],
        ];
    }

    /** Autoevaluación por habilidad, cuando no se pudo generar un cuestionario con respuestas correctas. */
    public function selfAssessment(array $skills): array
    {
        return collect($skills)->map(fn ($skill) => [
            'kind' => 'self',
            'skill' => $skill['name'],
            'difficulty' => 1,
            'question' => '¿Qué tan bien manejas: '.Str::lcfirst($skill['name']).'?',
            'options' => ['Nunca lo he hecho', 'Lo he hecho con ayuda', 'Lo hago por mi cuenta', 'Podría enseñarlo'],
        ])->values()->all();
    }

    /** Cuestionario completo de respaldo a partir de un análisis (también de respaldo). */
    public function fallbackQuestions(array $analysis): array
    {
        return array_merge($this->contextQuestions(), $this->selfAssessment($analysis['skills'] ?? []));
    }

    /** @return array{0: array<int, string>, 1: array<int, string>} */
    private function problems(array $value, array $skills, int $count): array
    {
        [$hard, $soft] = [[], []];
        $questions = $value['questions'] ?? [];
        foreach ($skills as $skill) {
            $own = collect($questions)->filter(fn ($question) => $this->matchSkill((string) ($question['skill'] ?? ''), $skills) === $skill['name']);
            if ($own->count() < 2) {
                $hard[] = 'La habilidad "'.$skill['name'].'" necesita '.$count.' preguntas (usa su nombre exacto en skill).';
            } elseif ($own->pluck('difficulty')->unique()->count() < 2) {
                $soft[] = 'Las preguntas de "'.$skill['name'].'" deben tener dificultades distintas.';
            }
        }
        foreach ($questions as $index => $question) {
            $options = $question['options'] ?? [];
            $answer = $question['answer'] ?? null;
            if (count($options) !== 4 || ! is_int($answer) || $answer < 0 || $answer > 3) {
                $hard[] = 'La pregunta '.($index + 1).' necesita 4 opciones y un índice de respuesta entre 0 y 3.';
            } elseif (count(array_unique(array_map(fn ($option) => Str::lower(trim((string) $option)), $options))) < 4) {
                $hard[] = 'La pregunta '.($index + 1).' tiene opciones repetidas.';
            }
            if (! in_array($question['difficulty'] ?? null, [1, 2, 3], true)) {
                $hard[] = 'La dificultad de la pregunta '.($index + 1).' debe ser 1, 2 o 3.';
            }
        }
        $positions = collect($questions)->pluck('answer')->countBy();
        if ($questions && $positions->max() > count($questions) * 0.5) {
            $soft[] = 'La respuesta correcta está casi siempre en la misma posición: repártela entre las cuatro opciones.';
        }

        return [$hard, $soft];
    }

    private function matchSkill(string $name, array $skills): string
    {
        $normalize = fn (string $text) => Str::lower(Str::ascii(trim($text)));
        foreach ($skills as $skill) {
            if ($normalize($skill['name']) === $normalize($name)) {
                return $skill['name'];
            }
        }

        return $name;
    }

    private function schema(int $total): array
    {
        $string = ['type' => 'string'];

        return ['type' => 'object', 'additionalProperties' => false, 'required' => ['questions'], 'properties' => ['questions' => ['type' => 'array', 'minItems' => max(4, $total - 2), 'maxItems' => $total + 2, 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['skill', 'difficulty', 'question', 'options', 'answer', 'explanation'], 'properties' => [
            'skill' => $string,
            'difficulty' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 3],
            'question' => $string,
            'options' => ['type' => 'array', 'minItems' => 4, 'maxItems' => 4, 'items' => $string],
            'answer' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 3],
            'explanation' => $string,
        ]]]]];
    }
}
