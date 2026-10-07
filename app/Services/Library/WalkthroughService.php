<?php

namespace App\Services\Library;

use App\AI\ReviewedGenerator;
use App\Models\LibraryTopic;

/**
 * Extracto animado de un tema de Office: un guion de 5 a 10 pasos que se reproduce sobre una ventana simulada de
 * Excel, Word, PowerPoint u Outlook (pestaña, botón, celda, texto), con subtítulos y narración. No es un video:
 * es una escena que el navegador anima, liviana y siempre nítida. Los resultados de Excel los calcula el sistema.
 */
class WalkthroughService
{
    /** Pestañas de la cinta de opciones de Microsoft 365 en español, por aplicación. */
    public const TABS = [
        'excel' => ['Archivo', 'Inicio', 'Insertar', 'Disposición de página', 'Fórmulas', 'Datos', 'Revisar', 'Vista', 'Programador'],
        'word' => ['Archivo', 'Inicio', 'Insertar', 'Diseño', 'Disposición', 'Referencias', 'Correspondencia', 'Revisar', 'Vista', 'Programador'],
        'powerpoint' => ['Archivo', 'Inicio', 'Insertar', 'Diseño', 'Transiciones', 'Animaciones', 'Presentación con diapositivas', 'Grabar', 'Revisar', 'Vista'],
        'outlook' => ['Archivo', 'Inicio', 'Enviar y recibir', 'Carpeta', 'Vista', 'Mensaje', 'Insertar', 'Opciones'],
    ];

    public const ACTIONS = ['tab', 'click', 'select', 'type', 'fill', 'format', 'note'];

    public const FORMATS = ['', 'negrita', 'cursiva', 'subrayado', 'titulo1', 'titulo2', 'centrar', 'derecha', 'vinetas', 'numeracion', 'resaltar', 'moneda', 'porcentaje', 'tabla', 'grafico'];

    private const MAX_COLUMNS = 8;

    private const MAX_ROWS = 14;

    public function __construct(private readonly ReviewedGenerator $reviewed, private readonly ExcelFormulaEvaluator $excel, private readonly ElevenLabsNarrator $narrator) {}

    /** @return array<string, mixed>|null el extracto listo, o null si no se pudo generar uno válido */
    public function generate(LibraryTopic $topic): ?array
    {
        $app = $topic->tool;
        $review = 'Revisa como especialista en Microsoft '.$topic->toolLabel().' (Microsoft 365 en español) este guion de un extracto animado. Verifica: '
            .'1) que cada pestaña y cada botón existan con ese nombre exacto en la versión en español y estén en esa pestaña (por ejemplo, Autosuma está en Inicio y en Fórmulas; Tabla dinámica en Insertar; Control de cambios en Revisar); '
            .'2) que los pasos sigan el orden real en que se hace en el programa, sin saltarse clics; 3) que en Excel cada fórmula use celdas que existen en la hoja y que result sea el resultado correcto; '
            .'4) que la narración sea natural para escucharse en voz alta, en español con tuteo, sin leer símbolos raros (di "igual SUMA, abre paréntesis, B2 dos puntos B6" solo si ayuda); 5) que el ejemplo sea el mismo de la lección. '
            .'Corrige lo que esté mal. En review_notes anota cada corrección. Devuelve el guion completo.';

        $value = $this->reviewed->generate('tutorial_script', $this->prompt($topic), $review, $this->schema(), 'library_walkthrough', (int) config('ai.agents.tutorial_script.max_tokens', 2600), null,
            fn (array $value) => $this->problems($value, $app), fn () => []);
        if ($value === []) {
            return null;
        }

        $walkthrough = $this->normalize($value, $app);
        if ($this->narrator->configured()) {
            $narration = $this->narrator->narrate("library/narration/{$topic->id}", array_column($walkthrough['steps'], 'narration'));
            foreach ($walkthrough['steps'] as $index => &$step) {
                $step['audio'] = $narration['files'][$index] !== null;
            }
            unset($step);
            $walkthrough['narration'] = ['provider' => 'elevenlabs', 'voice' => $narration['voice'], 'model' => $narration['model'], 'characters' => $narration['characters']];
        }

        return $walkthrough;
    }

    private function prompt(LibraryTopic $topic): string
    {
        $tabs = implode(', ', self::TABS[$topic->tool]);
        $stage = match ($topic->tool) {
            'excel' => 'sheet.rows es la hoja inicial a partir de A1 (la primera fila son encabezados), con '.self::MAX_COLUMNS.' columnas como máximo y '.self::MAX_ROWS.' filas como máximo, valores sin símbolo de moneda ni separador de miles; las celdas que se llenarán con fórmulas van vacías. document va con title y meta vacíos y paragraphs vacío. '
                .'En los pasos, target es una celda (D2) o un rango (B2:B6); action "type" escribe value en la celda target (si es fórmula empieza con = y usa nombres de funciones en español, con coma como separador) y result es el resultado que se ve; action "fill" copia con el controlador de relleno la fórmula de la primera celda de target a todo el rango (value es la fórmula de la primera celda); action "select" selecciona target; action "format" aplica value (moneda, porcentaje, negrita, tabla, grafico…) a target.',
            'word' => 'document.title es el nombre del documento y document.paragraphs son de 3 a 6 párrafos cortos del documento de ejemplo (el primero puede ser el título). sheet.rows va vacío. En los pasos, target es el número de párrafo (1, 2, 3…) sobre el que se actúa; action "select" lo selecciona, "type" agrega o reemplaza su texto con value y "format" le aplica value (negrita, cursiva, subrayado, titulo1, titulo2, centrar, derecha, vinetas, numeracion, resaltar).',
            'powerpoint' => 'document.title es el título de la diapositiva y document.paragraphs son de 2 a 5 viñetas breves; document.meta es el nombre del tema de diseño. sheet.rows va vacío. En los pasos, target es "titulo" o el número de viñeta (1, 2, 3…); "type" escribe value, "format" aplica value (negrita, centrar, vinetas, resaltar, grafico, tabla) y "click" sobre un botón de Animaciones o Transiciones muestra el efecto.',
            default => 'document.title es el asunto del correo o de la reunión, document.meta indica destinatarios o fecha (por ejemplo "Para: compras@ceiba.com.gt · CC: gerencia@ceiba.com.gt") y document.paragraphs son de 2 a 5 líneas del cuerpo. sheet.rows va vacío. En los pasos, target es "para", "cc", "asunto" o el número de línea del cuerpo; "type" escribe value ahí.',
        };

        return 'Eres guionista de tutoriales de Microsoft '.$topic->toolLabel().'. Diseña un extracto animado corto (no un video) que muestre, sobre una ventana simulada del programa, cómo se hace lo esencial del tema, paso a paso y con narración en voz alta.'."\n"
            .'Tema: "'.$topic->title.'" (nivel '.$topic->levelLabel().'). Objetivo: '.$topic->learning_objective."\n"
            .'Usa el mismo caso del ejemplo resuelto de la lección: '.json_encode(data_get($topic->lesson, 'worked_example', []), JSON_UNESCAPED_UNICODE)."\n"
            .'Requisitos: file_name es el nombre del archivo de ejemplo con su extensión. intro: una oración que dice qué se verá. '
            .'steps: de 5 a 10 pasos, en el orden real. Cada paso tiene: action (tab = abrir una pestaña de la cinta; click = pulsar un botón, menú o cuadro de diálogo; select; type; fill; format; note = solo explicar sin tocar nada), '
            .'tab (la pestaña donde está el botón, exactamente una de: '.$tabs.'; vacío si no aplica), control (nombre exacto del botón, menú o comando en español, por ejemplo "Autosuma", "Tabla dinámica", "Combinar correspondencia"; vacío si no aplica), '
            .'target, value, result (vacíos si no aplican) y narration: lo que dice la voz en ese paso, de 12 a 35 palabras, explicando qué se hace y por qué. '
            .$stage.' summary: una oración con lo que se logró. '
            .'Español latinoamericano con tuteo. Datos y empresas ficticios pero verosímiles en Guatemala; montos en quetzales.';
    }

    /** @return array{0: list<string>, 1: list<string>} */
    public function problems(array $value, string $app): array
    {
        [$hard, $soft] = [[], []];
        $steps = $value['steps'] ?? [];
        if (count($steps) < 4) {
            $hard[] = 'El extracto necesita al menos 5 pasos.';
        }
        $rows = $value['sheet']['rows'] ?? [];
        if ($app === 'excel' && (count($rows) < 2 || count($rows[0] ?? []) < 2)) {
            $hard[] = 'La hoja necesita encabezados y al menos una fila de datos.';
        }
        if ($app !== 'excel' && count($value['document']['paragraphs'] ?? []) < 2) {
            $hard[] = 'El documento de ejemplo necesita al menos 2 párrafos.';
        }
        foreach ($steps as $index => $step) {
            $n = $index + 1;
            $words = str_word_count((string) ($step['narration'] ?? ''));
            if (trim((string) ($step['narration'] ?? '')) === '') {
                $hard[] = "El paso {$n} no tiene narración.";
            } elseif ($words > 45) {
                $soft[] = "La narración del paso {$n} es larga ({$words} palabras): déjala en 35 como máximo.";
            }
            if (($step['tab'] ?? '') !== '' && ! in_array($step['tab'], self::TABS[$app], true)) {
                $soft[] = "La pestaña \"{$step['tab']}\" del paso {$n} no es una pestaña de la cinta de {$app}.";
            }
            if ($app === 'excel' && in_array($step['action'] ?? '', ['type', 'fill', 'select', 'format'], true) && ! preg_match('/^\$?[A-Z]{1,2}\$?\d{1,3}(:\$?[A-Z]{1,2}\$?\d{1,3})?$/', (string) ($step['target'] ?? ''))) {
                $soft[] = "El paso {$n} debe indicar en target una celda o un rango (por ejemplo D2 o B2:B6).";
            }
        }

        return [$hard, $soft];
    }

    /** Deja el guion en el formato del reproductor y calcula los resultados de Excel. */
    public function normalize(array $value, string $app): array
    {
        $sheet = array_values(array_slice(array_map(fn ($row) => array_values(array_slice(array_map(fn ($cell) => mb_substr(trim((string) $cell), 0, 40), (array) $row), 0, self::MAX_COLUMNS)), $value['sheet']['rows'] ?? []), 0, self::MAX_ROWS));
        $cells = $this->cells($sheet);

        $steps = [];
        foreach (array_slice($value['steps'] ?? [], 0, 10) as $step) {
            $clean = [
                'action' => in_array($step['action'] ?? '', self::ACTIONS, true) ? $step['action'] : 'note',
                'tab' => (string) ($step['tab'] ?? ''), 'control' => (string) ($step['control'] ?? ''),
                'target' => mb_strtoupper(str_replace('$', '', trim((string) ($step['target'] ?? '')))), 'value' => (string) ($step['value'] ?? ''),
                'result' => (string) ($step['result'] ?? ''), 'narration' => trim((string) ($step['narration'] ?? '')), 'results' => [], 'audio' => false,
            ];
            if ($app === 'excel') {
                $clean = $this->applyExcel($clean, $cells);
            } else {
                $clean['target'] = mb_strtolower($clean['target']);
            }
            $steps[] = $clean;
        }

        return [
            'version' => 1, 'app' => $app, 'file_name' => (string) ($value['file_name'] ?? ''), 'intro' => (string) ($value['intro'] ?? ''),
            'sheet' => $sheet, 'document' => ['title' => (string) data_get($value, 'document.title', ''), 'meta' => (string) data_get($value, 'document.meta', ''), 'paragraphs' => array_values(array_map('strval', data_get($value, 'document.paragraphs', [])))],
            'steps' => $steps, 'summary' => (string) ($value['summary'] ?? ''), 'narration' => null,
        ];
    }

    /**
     * Valores de la hoja por celda (A1 => "Vendedor").
     *
     * @param  list<list<string>>  $sheet
     * @return array<string, string>
     */
    private function cells(array $sheet): array
    {
        $cells = [];
        foreach ($sheet as $r => $row) {
            foreach ($row as $c => $cell) {
                $cells[ExcelFormulaEvaluator::column($c + 1).($r + 1)] = $cell;
            }
        }

        return $cells;
    }

    /**
     * @param  array{action: string, tab: string, control: string, target: string, value: string, result: string, narration: string, results: array<string, string>, audio: bool}  $step
     * @param  array<string, string>  $cells  hoja actual; se actualiza con lo que escribe el paso
     * @return array<string, mixed>
     */
    private function applyExcel(array $step, array &$cells): array
    {
        $target = $step['target'];
        if ($step['action'] === 'type' && preg_match('/^[A-Z]{1,2}\d{1,3}$/', $target)) {
            $result = str_starts_with($step['value'], '=') ? $this->excel->evaluate($step['value'], $cells) : null;
            $shown = $result !== null ? ExcelFormulaEvaluator::format($result) : ($step['result'] !== '' ? $step['result'] : $step['value']);
            $step['results'] = [$target => $shown];
            $step['result'] = $shown;
            $step['verified'] = $result !== null;
            $cells[$target] = $shown;
        } elseif ($step['action'] === 'fill' && preg_match('/^([A-Z]{1,2})(\d{1,3}):([A-Z]{1,2})(\d{1,3})$/', $target, $m) && str_starts_with($step['value'], '=')) {
            [$startCol, $startRow, $endCol, $endRow] = [$this->columnIndex($m[1]), (int) $m[2], $this->columnIndex($m[3]), (int) $m[4]];
            $results = [];
            $verified = true;
            for ($row = $startRow; $row <= $endRow; $row++) {
                for ($col = $startCol; $col <= $endCol; $col++) {
                    $formula = $this->shift($step['value'], $row - $startRow, $col - $startCol);
                    $result = $this->excel->evaluate($formula, $cells);
                    $verified = $verified && $result !== null;
                    $ref = ExcelFormulaEvaluator::column($col).$row;
                    $results[$ref] = $result !== null ? ExcelFormulaEvaluator::format($result) : '…';
                    $cells[$ref] = $results[$ref];
                }
            }
            $step['results'] = $results;
            $step['verified'] = $verified;
        }

        return $step;
    }

    /** Mueve las referencias relativas de una fórmula al copiarla (las que llevan $ no cambian). */
    public function shift(string $formula, int $rows, int $columns): string
    {
        return preg_replace_callback('/(\$?)([A-Z]{1,2})(\$?)(\d{1,4})(?![A-Z(])/', function (array $m) use ($rows, $columns) {
            $column = $m[1] === '$' ? $m[2] : ExcelFormulaEvaluator::column($this->columnIndex($m[2]) + $columns);
            $row = $m[3] === '$' ? $m[4] : (string) ((int) $m[4] + $rows);

            return $m[1].$column.$m[3].$row;
        }, $formula) ?? $formula;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index;
    }

    private function schema(): array
    {
        $string = ['type' => 'string'];
        $object = fn (array $properties) => ['type' => 'object', 'additionalProperties' => false, 'required' => array_keys($properties), 'properties' => $properties];

        return $object([
            'file_name' => $string,
            'intro' => $string,
            'sheet' => $object(['rows' => ['type' => 'array', 'maxItems' => self::MAX_ROWS, 'items' => ['type' => 'array', 'maxItems' => self::MAX_COLUMNS, 'items' => $string]]]),
            'document' => $object(['title' => $string, 'meta' => $string, 'paragraphs' => ['type' => 'array', 'maxItems' => 6, 'items' => $string]]),
            'steps' => ['type' => 'array', 'minItems' => 4, 'maxItems' => 10, 'items' => $object([
                'action' => ['type' => 'string', 'enum' => self::ACTIONS],
                'tab' => $string, 'control' => $string, 'target' => $string, 'value' => $string, 'result' => $string, 'narration' => $string,
            ])],
            'summary' => $string,
        ]);
    }
}
