<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Las rutas creadas antes de pipeline-v1 guardaron mini-quizzes de fórmulas de Excel
 * con el índice de respuesta incorrecto. Antes se corregían al vuelo en cada intento;
 * esta migración los corrige una sola vez en los datos.
 */
return new class extends Migration
{
    private const FORMULAS = [
        '=a1+b1' => ['suma', 'sumar'],
        '=a1-b1' => ['resta', 'restar'],
        '=a1*b1' => ['multiplica', 'multiplicación', 'producto'],
        '=a1/b1' => ['divide', 'división', 'reparte'],
    ];

    public function up(): void
    {
        DB::table('learning_path_items')
            ->whereNotNull('quiz')
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    $quiz = json_decode((string) $item->quiz, true);
                    if (! is_array($quiz)) {
                        continue;
                    }

                    $answer = $this->formulaAnswer($quiz);
                    if ($answer !== null && $answer !== (int) ($quiz['answer'] ?? -1)) {
                        $quiz['answer'] = $answer;
                        DB::table('learning_path_items')->where('id', $item->id)->update(['quiz' => json_encode($quiz, JSON_UNESCAPED_UNICODE)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Corrección de datos irreversible: no se restauran respuestas incorrectas.
    }

    private function formulaAnswer(array $quiz): ?int
    {
        $question = Str::lower((string) ($quiz['question'] ?? ''));

        foreach (self::FORMULAS as $formula => $keywords) {
            if (! str_contains($question, $formula)) {
                continue;
            }

            foreach ($quiz['options'] ?? [] as $index => $option) {
                if (Str::contains(Str::lower((string) $option), $keywords)) {
                    return (int) $index;
                }
            }
        }

        return null;
    }
};
