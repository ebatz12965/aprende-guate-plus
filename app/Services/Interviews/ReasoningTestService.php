<?php

namespace App\Services\Interviews;

use App\AI\AiGateway;
use App\Models\InterviewSimulation;
use App\Models\InterviewStage;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Prueba de razonamiento y juicio situacional: 12 preguntas de opción múltiple (3 de cada tipo).
 *
 * - Numéricas y lógicas: las arma PHP con datos aleatorios, así la respuesta correcta es exacta.
 * - Lectura y juicio situacional: las redacta la IA según el puesto, con un banco de respaldo.
 * La calificación es exacta y sin IA. Es práctica: no es un instrumento psicométrico validado.
 */
class ReasoningTestService
{
    public const CATEGORIES = [
        'logic' => 'Razonamiento lógico',
        'numeric' => 'Razonamiento numérico',
        'verbal' => 'Comprensión de lectura',
        'situational' => 'Juicio situacional',
    ];

    private const NAMES = ['Ana', 'Luis', 'Marta', 'Diego', 'Sofía', 'Carlos', 'Lucía', 'Jorge'];

    private const VERBAL = [
        ['passage' => 'A partir del lunes, toda solicitud de compra mayor de Q 2,000 necesita la firma de la gerencia antes de enviarse al proveedor. Las solicitudes menores siguen el proceso de siempre: la aprueba la jefatura del área.', 'question' => 'Una jefa de área necesita comprar una impresora de Q 2,450. ¿Qué debe hacer?', 'options' => ['Pedir la firma de la gerencia antes de enviarla al proveedor', 'Aprobarla ella misma porque es jefa de área', 'Enviarla al proveedor y avisar a la gerencia después', 'Dividirla en dos compras de Q 1,225'], 'explanation' => 'El monto supera Q 2,000, así que la gerencia debe firmar antes de enviarla. Dividir la compra para evitar la regla no es correcto.'],
        ['passage' => 'El servicio de soporte atiende de 7:00 a 17:00. Fuera de ese horario solo se atienden fallas que detienen la operación de una bodega, y deben reportarse por teléfono, no por correo.', 'question' => 'Son las 19:30 y el sistema de inventario de una bodega dejó de funcionar. ¿Qué indica el texto?', 'options' => ['Reportarlo por teléfono porque detiene la operación', 'Enviar un correo para que lo revisen mañana', 'Esperar a las 7:00 para reportarlo', 'No reportarlo porque está fuera de horario'], 'explanation' => 'Es una falla que detiene la operación de una bodega, el único caso que se atiende fuera de horario, y se reporta por teléfono.'],
        ['passage' => 'Las vacaciones se solicitan con al menos quince días de anticipación. Si dos personas del mismo equipo piden las mismas fechas, tiene prioridad quien las pidió primero.', 'question' => 'Pedro pidió vacaciones del 10 al 14 hace un mes; Raquel, de su mismo equipo, pidió las mismas fechas ayer. ¿Qué sucede?', 'options' => ['Pedro tiene prioridad porque las pidió primero', 'Raquel tiene prioridad porque su solicitud es más reciente', 'Ninguno puede tomarlas', 'Las fechas se reparten a la mitad'], 'explanation' => 'El texto da prioridad a quien solicitó primero.'],
    ];

    private const SITUATIONAL = [
        ['situation' => 'Te asignan una tarea urgente, pero no entiendes bien lo que se espera y tu jefe está en una reunión.', 'options' => ['Avanzar con lo que sí entiendes y dejarle un mensaje breve con tus dudas puntuales', 'Esperar sin hacer nada hasta que salga de la reunión', 'Hacerla como creas sin preguntar nada', 'Pedirle a un compañero que la haga por ti'], 'explanation' => 'Avanzar en lo claro y dejar dudas concretas muestra iniciativa sin arriesgar el resultado.'],
        ['situation' => 'Te das cuenta de que cometiste un error en un reporte que ya enviaste a otra área.', 'options' => ['Avisar de inmediato, enviar la versión corregida y explicar qué cambió', 'No decir nada y esperar a que nadie lo note', 'Culpar a la información que te dieron', 'Corregirlo solo en tu copia'], 'explanation' => 'Reconocer el error a tiempo y corregirlo genera confianza y evita decisiones con datos equivocados.'],
        ['situation' => 'Un compañero te pide ayuda con algo que no es tu responsabilidad, justo cuando tienes una entrega en una hora.', 'options' => ['Explicarle que tienes una entrega y acordar ayudarle después a una hora concreta', 'Dejar tu entrega para ayudarle', 'Decirle que no es tu problema', 'Ignorar el mensaje'], 'explanation' => 'Cumples tu compromiso y mantienes la colaboración acordando un momento claro para ayudar.'],
    ];

    public function __construct(private readonly AiGateway $gateway) {}

    /**
     * @return array{items: list<array{id: string, category: string, prompt: string, passage?: ?string, options: list<string>, answer: int, explanation: string}>, source: string}
     */
    public function build(InterviewSimulation $simulation, InterviewStage $stage): array
    {
        $random = new Randomizer(new Mt19937($simulation->id * 1000 + $stage->id));
        $written = $this->written($simulation, $stage);
        $items = [
            // Mismo orden en que se muestran (CATEGORIES), así el número de cada pregunta coincide.
            ...$this->logic($random),
            ...$this->numeric($random),
            ...$written['verbal'],
            ...$written['situational'],
        ];

        // Las opciones se barajan aquí: el modelo tiende a poner la correcta en el mismo lugar.
        $items = array_map(function (array $item, int $index) use ($random) {
            $order = $random->shuffleArray(array_keys($item['options']));
            $item['answer'] = array_search($item['answer'], $order, true);
            $item['options'] = array_map(fn (int $key) => $item['options'][$key], $order);
            $item['id'] = 'q'.($index + 1);

            return $item;
        }, $items, array_keys($items));

        return ['items' => $items, 'source' => $written['source']];
    }

    /**
     * Calificación exacta. $choices: id de la pregunta => opción elegida.
     *
     * @param  array<string, int|string|null>  $choices
     * @return array{score: int, correct: int, total: int, summary: string, categories: list<array{key: string, name: string, correct: int, total: int}>, items: array<string, array{chosen: ?int, correct: bool}>}
     */
    public function grade(array $items, array $choices): array
    {
        $results = [];
        $byCategory = [];
        foreach ($items as $item) {
            $chosen = isset($choices[$item['id']]) && is_numeric($choices[$item['id']]) ? (int) $choices[$item['id']] : null;
            $right = $chosen !== null && $chosen === $item['answer'];
            $results[$item['id']] = ['chosen' => $chosen, 'correct' => $right];
            $byCategory[$item['category']]['total'] = ($byCategory[$item['category']]['total'] ?? 0) + 1;
            $byCategory[$item['category']]['correct'] = ($byCategory[$item['category']]['correct'] ?? 0) + ($right ? 1 : 0);
        }
        $correct = collect($results)->where('correct', true)->count();
        $total = count($items);
        $categories = collect(self::CATEGORIES)->filter(fn ($name, $key) => isset($byCategory[$key]))
            ->map(fn (string $name, string $key) => ['key' => $key, 'name' => $name, 'correct' => $byCategory[$key]['correct'], 'total' => $byCategory[$key]['total']])->values()->all();

        $best = collect($categories)->sortByDesc(fn ($c) => $c['correct'] / $c['total'])->first();
        $worst = collect($categories)->sortBy(fn ($c) => $c['correct'] / $c['total'])->first();
        $summary = "Respondiste bien {$correct} de {$total} preguntas.";
        if ($best && $worst && $best['key'] !== $worst['key']) {
            $summary .= " Tu punto fuerte fue {$this->lower($best['name'])} ({$best['correct']} de {$best['total']}); para practicar, {$this->lower($worst['name'])} ({$worst['correct']} de {$worst['total']}).";
        }

        return [
            'score' => $total ? (int) round($correct / $total * 100) : 0,
            'correct' => $correct,
            'total' => $total,
            'summary' => $summary,
            'categories' => $categories,
            'items' => $results,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function numeric(Randomizer $random): array
    {
        $money = fn (float $value) => 'Q '.number_format($value, 2);
        $generators = [
            function () use ($random, $money) {
                $price = [250, 300, 450, 600, 800, 1200][$random->getInt(0, 5)];
                $discount = [10, 15, 20, 25][$random->getInt(0, 3)];
                $final = $price * (100 - $discount) / 100;

                return $this->item('numeric', "Un monitor cuesta {$money($price)} y tiene un descuento del {$discount} %. ¿Cuánto se paga?",
                    $money($final), [$money($price * $discount / 100), $money($price - $discount), $money($final + $price * 0.05)],
                    "El descuento es {$money($price * $discount / 100)}; se paga {$money($price)} − {$money($price * $discount / 100)} = {$money($final)}.");
            },
            function () use ($random) {
                $people = $random->getInt(2, 4);
                $perPerson = $random->getInt(3, 6);
                $team = $random->getInt(5, 8);

                return $this->item('numeric', 'Si '.$people.' técnicos configuran '.($people * $perPerson).' computadoras en un día, ¿cuántas configuran '.$team.' técnicos al mismo ritmo?',
                    (string) ($team * $perPerson), [(string) ($people * $perPerson + $team - $people), (string) ($team * $perPerson + $perPerson), (string) ($team * $perPerson - $perPerson)],
                    'Cada técnico configura '.$perPerson.' por día; '.$team.' × '.$perPerson.' = '.($team * $perPerson).'.');
            },
            function () use ($random) {
                // Cuatro días al azar y el quinto calculado para que el promedio sea un número entero.
                do {
                    $average = $random->getInt(15, 25);
                    $values = array_map(fn () => $random->getInt(10, 30), range(1, 4));
                    $values[] = $average * 5 - array_sum($values);
                } while ($values[4] < 8 || $values[4] > 35);

                return $this->item('numeric', 'Los tickets atendidos de lunes a viernes fueron: '.implode(', ', $values).'. ¿Cuál fue el promedio diario?',
                    (string) $average, [(string) ($average + 2), (string) ($average - 3), (string) (max($values) === $average ? $average + 5 : max($values))],
                    'Total '.array_sum($values).' ÷ 5 días = '.$average.'.');
            },
            function () use ($random, $money) {
                $budget = [2000, 5000, 8000, 10000][$random->getInt(0, 3)];
                $spent = [25, 40, 60, 75][$random->getInt(0, 3)];
                $left = $budget * (100 - $spent) / 100;

                return $this->item('numeric', "Un área tiene un presupuesto de {$money($budget)} y ya gastó el {$spent} %. ¿Cuánto le queda?",
                    $money($left), [$money($budget * $spent / 100), $money($left / 2), $money($budget - $spent)],
                    "Gastó {$money($budget * $spent / 100)}; quedan {$money($budget)} − {$money($budget * $spent / 100)} = {$money($left)}.");
            },
        ];

        return array_map(fn (callable $make) => $make(), array_slice($random->shuffleArray($generators), 0, 3));
    }

    /** @return list<array<string, mixed>> */
    private function logic(Randomizer $random): array
    {
        $start = $random->getInt(2, 9);
        $step = $random->getInt(3, 9);
        $terms = array_map(fn (int $i) => $start + $i * $step, range(0, 4));
        $next = $start + 5 * $step;
        $arithmetic = $this->item('logic', '¿Qué número sigue? '.implode(', ', $terms).', …', (string) $next, [(string) ($next + $step), (string) ($next - 1), (string) ($next + 2)],
            'Cada número suma '.$step.' al anterior: '.end($terms).' + '.$step.' = '.$next.'.');

        $base = $random->getInt(2, 4);
        $ratio = $random->getInt(2, 3);
        $series = array_map(fn (int $i) => $base * $ratio ** $i, range(0, 4));
        $after = $base * $ratio ** 5;
        $geometric = $this->item('logic', '¿Qué número sigue? '.implode(', ', $series).', …', (string) $after,
            [(string) (end($series) + $ratio), (string) ($after - $base), (string) ($after + $series[4])],
            'Cada número se multiplica por '.$ratio.': '.end($series).' × '.$ratio.' = '.$after.'.');

        $people = array_slice($random->shuffleArray(self::NAMES), 0, 4);
        [$a, $b, $c, $d] = $people;
        $place = $random->getInt(1, 3);
        $labels = [1 => 'en segundo lugar', 2 => 'en tercer lugar', 3 => 'de último'];
        $order = $this->item('logic', "{$a} llegó antes que {$b}. {$c} llegó después de {$b}. {$d} fue la última persona en llegar. ¿Quién llegó {$labels[$place]}?",
            $people[$place], array_values(array_diff($people, [$people[$place]])),
            "El orden de llegada es {$a}, {$b}, {$c} y {$d}.");

        return [$arithmetic, $geometric, $order];
    }

    /** @return array{verbal: list<array<string, mixed>>, situational: list<array<string, mixed>>, source: string} */
    private function written(InterviewSimulation $simulation, InterviewStage $stage): array
    {
        $fallback = [
            'verbal' => array_map(fn (array $q) => $this->item('verbal', $q['question'], $q['options'][0], array_slice($q['options'], 1), $q['explanation'], $q['passage']), self::VERBAL),
            'situational' => array_map(fn (array $q) => $this->item('situational', $q['situation'].' ¿Qué harías?', $q['options'][0], array_slice($q['options'], 1), $q['explanation']), self::SITUATIONAL),
        ];

        $prompt = 'Crea preguntas de práctica para una prueba de selección SIMULADA en Guatemala. Puesto: '.$simulation->job_title.' ('.$simulation->areaLabel().'). '
            .'Contexto de trabajo: '.$stage->purpose
            ."\n- verbal: 3 preguntas de comprensión de lectura. passage es un texto de trabajo de 2 a 4 oraciones (una política, un procedimiento o un aviso); question se responde SOLO con el texto."
            ."\n- situational: 3 situaciones realistas del puesto; situation describe el caso en una o dos oraciones; las opciones son acciones posibles."
            ."\nCada pregunta tiene exactamente 4 opciones, una sola claramente mejor; correct es el número (0 a 3) de la mejor opción; explanation dice por qué en una oración."
            ."\nNo uses temas de edad, religión, estado civil, hijos, etnia, orientación sexual, política, discapacidad, nacionalidad ni apariencia.";
        $question = ['type' => 'object', 'additionalProperties' => false, 'required' => ['options', 'correct', 'explanation'], 'properties' => ['options' => ['type' => 'array', 'items' => ['type' => 'string']], 'correct' => ['type' => 'integer'], 'explanation' => ['type' => 'string']]];
        $verbal = $question;
        $verbal['required'] = ['passage', 'question', 'options', 'correct', 'explanation'];
        $verbal['properties'] += ['passage' => ['type' => 'string'], 'question' => ['type' => 'string']];
        $situational = $question;
        $situational['required'] = ['situation', 'options', 'correct', 'explanation'];
        $situational['properties'] += ['situation' => ['type' => 'string']];
        $value = $this->gateway->structured('interviewer', $prompt, ['type' => 'object', 'additionalProperties' => false, 'required' => ['verbal', 'situational'], 'properties' => [
            'verbal' => ['type' => 'array', 'items' => $verbal], 'situational' => ['type' => 'array', 'items' => $situational],
        ]], 'interview_reasoning', $simulation->user_id, 2000, (int) config('ai.content_timeout'));

        $fromAi = 0;
        $result = [];
        foreach (['verbal', 'situational'] as $kind) {
            $clean = collect(is_array($value[$kind] ?? null) ? $value[$kind] : [])->map(fn ($q) => $this->cleanWritten($kind, $q))->filter()->take(3)->values()->all();
            if (count($clean) === 3) {
                $fromAi++;
            }
            $result[$kind] = count($clean) === 3 ? $clean : $fallback[$kind];
        }

        return $result + ['source' => $fromAi === 2 ? 'ia' : ($fromAi === 1 ? 'ia+plantilla' : 'plantilla')];
    }

    private function cleanWritten(string $kind, mixed $q): ?array
    {
        if (! is_array($q)) {
            return null;
        }
        $text = fn (mixed $v, int $max) => is_string($v) && trim($v) !== '' && mb_strlen(trim($v)) <= $max ? trim($v) : null;
        $options = collect(is_array($q['options'] ?? null) ? $q['options'] : [])->map(fn ($o) => $text($o, 300))->all();
        $correct = $q['correct'] ?? null;
        $explanation = $text($q['explanation'] ?? null, 500);
        $prompt = $kind === 'verbal' ? $text($q['question'] ?? null, 400) : $text($q['situation'] ?? null, 500);
        $passage = $kind === 'verbal' ? $text($q['passage'] ?? null, 900) : null;
        if (count($options) !== 4 || in_array(null, $options, true) || count(array_unique($options)) !== 4 || ! is_int($correct) || $correct < 0 || $correct > 3 || ! $explanation || ! $prompt || ($kind === 'verbal' && ! $passage)) {
            return null;
        }
        if (preg_match(InterviewPlanService::FORBIDDEN_TOPICS, $prompt.' '.$passage.' '.implode(' ', $options)) === 1) {
            return null;
        }
        $rest = array_values(array_diff_key($options, [$correct => true]));

        return $this->item($kind, $kind === 'situational' ? $prompt.' ¿Qué harías?' : $prompt, $options[$correct], $rest, $explanation, $passage);
    }

    /** Pregunta con la respuesta correcta en la posición 0 (se baraja después). */
    private function item(string $category, string $prompt, string $correct, array $distractors, string $explanation, ?string $passage = null): array
    {
        // Si dos distractores coinciden, se completa con opciones genéricas para mantener cuatro.
        $options = array_values(array_unique([$correct, ...$distractors, 'Ninguna de las anteriores', 'No se puede saber con estos datos']));

        return ['category' => $category, 'prompt' => $prompt, 'passage' => $passage, 'options' => array_slice($options, 0, 4), 'answer' => 0, 'explanation' => $explanation];
    }

    private function lower(string $text): string
    {
        return mb_strtolower(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }
}
