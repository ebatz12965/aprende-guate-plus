<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La aplicación pasó de UTC a America/Guatemala (UTC−6, sin horario de verano).
 * Las fechas generadas por el sistema (now()) se guardaron en UTC y se convierten a hora local.
 * Las fechas de mentoría escritas por las personas en formularios ya estaban en hora local.
 */
return new class extends Migration
{
    private const OFFSET_HOURS = 6;

    /** Columnas capturadas en hora local desde formularios: no se ajustan. */
    private const ENTERED_AS_LOCAL = [
        'mentorship_sessions' => ['scheduled_at', 'preferred_at'],
    ];

    public function up(): void
    {
        $this->shift(-self::OFFSET_HOURS);
    }

    public function down(): void
    {
        $this->shift(self::OFFSET_HOURS);
    }

    private function shift(int $hours): void
    {
        // En MySQL, getTableListing() sin esquema incluye las tablas de todas las bases del servidor.
        foreach (Schema::getTableListing($this->currentSchema(), schemaQualified: false) as $table) {
            if (str_starts_with($table, 'telescope_')) {
                continue;
            }

            foreach (Schema::getColumns($table) as $column) {
                $name = $column['name'];
                if (! in_array(strtolower($column['type_name']), ['datetime', 'timestamp'], true) || in_array($name, self::ENTERED_AS_LOCAL[$table] ?? [], true)) {
                    continue;
                }

                DB::table($table)->whereNotNull($name)->update([$name => DB::raw($this->shiftExpression($name, $hours))]);
            }
        }
    }

    private function currentSchema(): ?string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::connection()->getDatabaseName(),
            'pgsql' => DB::selectOne('select current_schema() as name')->name,
            default => null,
        };
    }

    private function shiftExpression(string $column, int $hours): string
    {
        $wrapped = DB::getQueryGrammar()->wrap($column);

        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => "DATE_ADD({$wrapped}, INTERVAL {$hours} HOUR)",
            'pgsql' => "{$wrapped} + INTERVAL '{$hours} hours'",
            'sqlite' => "datetime({$wrapped}, '".sprintf('%+d', $hours)." hours')",
            default => throw new RuntimeException('Motor de base de datos no soportado para ajustar zonas horarias.'),
        };
    }
};
