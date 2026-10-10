<?php

namespace App\Services\Library;

/**
 * Calcula fórmulas sencillas de Excel en español sobre la hoja de un extracto animado, para que los resultados
 * que se ven en pantalla sean exactos (los modelos de lenguaje se equivocan al sumar). Soporta números, celdas,
 * rangos, + - * / ^, paréntesis y SUMA, PROMEDIO, MAX, MIN, CONTAR, CONTARA y REDONDEAR. Si la fórmula usa algo
 * más (SI, BUSCARX…), devuelve null y se conserva el resultado que propuso el guion revisado.
 */
class ExcelFormulaEvaluator
{
    /** @var array<string, string> */
    private array $cells = [];

    /** @var list<array{0: string, 1: string}> */
    private array $tokens = [];

    private int $at = 0;

    /** @param  array<string, string>  $cells  valores por celda (A1 => "250") */
    public function evaluate(string $formula, array $cells): ?float
    {
        $formula = trim($formula);
        if (! str_starts_with($formula, '=')) {
            return null;
        }
        $this->cells = array_change_key_case($cells, CASE_UPPER);
        $this->tokens = $this->tokenize(substr($formula, 1));
        $this->at = 0;
        if ($this->tokens === []) {
            return null;
        }

        try {
            $value = $this->expression();
            if ($this->at !== count($this->tokens) || ! is_finite($value)) {
                return null;
            }

            return round($value, 10);
        } catch (\RuntimeException) {
            return null;
        }
    }

    public static function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /** @return list<array{0: string, 1: string}> */
    private function tokenize(string $text): array
    {
        preg_match_all('/\s*(?:(?<range>\$?[A-Z]{1,2}\$?\d{1,4}:\$?[A-Z]{1,2}\$?\d{1,4})|(?<cell>\$?[A-Z]{1,2}\$?\d{1,4})(?![A-Z(.])|(?<fn>[A-ZÁÉÍÓÚ][A-ZÁÉÍÓÚ.]*)\s*\(|(?<num>\d+(?:\.\d+)?)|(?<op>[-+*\/^(),;%]))/iu', $text, $matches, PREG_SET_ORDER);
        $consumed = implode('', array_column($matches, 0));
        if (preg_replace('/\s+/', '', $consumed) !== preg_replace('/\s+/', '', $text)) {
            return [];
        }

        return array_map(function (array $match) {
            foreach (['range', 'cell', 'fn', 'num', 'op'] as $type) {
                if (($match[$type] ?? '') !== '') {
                    return [$type, mb_strtoupper(str_replace('$', '', $match[$type]))];
                }
            }

            return ['op', ''];
        }, $matches);
    }

    private function peek(): ?array
    {
        return $this->tokens[$this->at] ?? null;
    }

    private function take(string $op): bool
    {
        if (($this->peek() ?? [null, null]) === ['op', $op]) {
            $this->at++;

            return true;
        }

        return false;
    }

    /** @phpstan-impure Avanza la posición de lectura. */
    private function expression(): float
    {
        $value = $this->term();
        while (true) {
            if ($this->take('+')) {
                $value += $this->term();
            } elseif ($this->take('-')) {
                $value -= $this->term();
            } else {
                return $value;
            }
        }
    }

    private function term(): float
    {
        $value = $this->power();
        while (true) {
            if ($this->take('*')) {
                $value *= $this->power();
            } elseif ($this->take('/')) {
                $divisor = $this->power();
                if ($divisor == 0) {
                    throw new \RuntimeException('División entre cero.');
                }
                $value /= $divisor;
            } else {
                return $value;
            }
        }
    }

    private function power(): float
    {
        $value = $this->unary();

        return $this->take('^') ? $value ** $this->power() : $value;
    }

    private function unary(): float
    {
        if ($this->take('-')) {
            return -$this->unary();
        }
        $this->take('+');
        $value = $this->primary();

        return $this->take('%') ? $value / 100 : $value;
    }

    private function primary(): float
    {
        $token = $this->peek() ?? throw new \RuntimeException('Fórmula incompleta.');
        $this->at++;

        return match ($token[0]) {
            'num' => (float) $token[1],
            'cell' => $this->number($token[1]),
            'fn' => $this->call($token[1]),
            'op' => $token[1] === '(' ? $this->group() : throw new \RuntimeException('Operador inesperado.'),
            default => throw new \RuntimeException('Rango fuera de una función.'),
        };
    }

    private function group(): float
    {
        $value = $this->expression();
        if (! $this->take(')')) {
            throw new \RuntimeException('Falta cerrar un paréntesis.');
        }

        return $value;
    }

    private function call(string $name): float
    {
        $values = [];
        $filled = 0;
        if (! $this->take(')')) {
            do {
                $token = $this->peek();
                if ($token && $token[0] === 'range') {
                    $this->at++;
                    foreach ($this->rangeCells($token[1]) as $ref) {
                        $raw = trim($this->cells[$ref] ?? '');
                        $filled += $raw !== '' ? 1 : 0;
                        if ($raw !== '' && is_numeric($this->clean($raw))) {
                            $values[] = (float) $this->clean($raw);
                        }
                    }
                } else {
                    $values[] = $this->expression();
                    $filled++;
                }
            } while ($this->take(',') || $this->take(';'));
            if (! $this->take(')')) {
                throw new \RuntimeException('Falta cerrar la función.');
            }
        }

        return match ($name) {
            'SUMA' => array_sum($values),
            'PROMEDIO' => $values ? array_sum($values) / count($values) : throw new \RuntimeException('Promedio sin números.'),
            'MAX' => $values ? max($values) : 0.0,
            'MIN' => $values ? min($values) : 0.0,
            'CONTAR' => (float) count($values),
            'CONTARA' => (float) $filled,
            'REDONDEAR' => count($values) === 2 ? round($values[0], (int) $values[1]) : throw new \RuntimeException('REDONDEAR necesita 2 argumentos.'),
            default => throw new \RuntimeException("Función no soportada: {$name}."),
        };
    }

    private function number(string $ref): float
    {
        $raw = $this->clean(trim($this->cells[$ref] ?? ''));
        if ($raw === '') {
            return 0.0;
        }
        if (! is_numeric($raw)) {
            throw new \RuntimeException("La celda {$ref} no es un número.");
        }

        return (float) $raw;
    }

    private function clean(string $raw): string
    {
        // Montos como "Q 1,250.50" o "15%" en la hoja de ejemplo.
        $percent = str_ends_with($raw, '%');
        $raw = str_replace(['Q', ' ', ',', '%'], '', $raw);

        return $percent && is_numeric($raw) ? (string) ((float) $raw / 100) : $raw;
    }

    /** @return list<string> */
    private function rangeCells(string $range): array
    {
        [$start, $end] = explode(':', $range);
        [$c1, $r1] = $this->split($start);
        [$c2, $r2] = $this->split($end);
        if (($c2 - $c1 + 1) * ($r2 - $r1 + 1) > 2000) {
            throw new \RuntimeException('Rango demasiado grande.');
        }
        $cells = [];
        for ($c = min($c1, $c2); $c <= max($c1, $c2); $c++) {
            for ($r = min($r1, $r2); $r <= max($r1, $r2); $r++) {
                $cells[] = self::column($c).$r;
            }
        }

        return $cells;
    }

    /** @return array{0: int, 1: int} */
    private function split(string $ref): array
    {
        preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
        $column = 0;
        foreach (str_split($m[1] ?? 'A') as $letter) {
            $column = $column * 26 + (ord($letter) - 64);
        }

        return [$column, (int) ($m[2] ?? 1)];
    }

    public static function column(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + $index % 26).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
