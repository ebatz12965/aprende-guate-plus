<?php

namespace App\Services\Learning;

use App\AI\ReviewedGenerator;
use Illuminate\Support\Str;

/** Diseña la estructura de módulos y temas de una ruta (agente learning_path). */
class LearningPathArchitect
{
    public const PRACTICE_TYPES = ['excel_dataset', 'code_bug', 'code_complete', 'case_study', 'language_activity', 'theory_quiz'];

    public function __construct(private readonly ReviewedGenerator $generator) {}

    /**
     * Diseña fases y temas desde el análisis de la meta y el nivel medido en cada habilidad.
     * Solo diseña la estructura: nunca genera lecciones ni prácticas en esta llamada.
     */
    public function design(string $goal, array $analysis, array $assessment, ?int $userId = null): array
    {
        $fallback = ($analysis['domain'] ?? null) === 'ofimatica' || Str::contains(Str::lower($goal), ['office', 'excel', 'word', 'powerpoint', 'ofimática', 'ofimatica'])
            ? config('office_curriculum.architecture')
            : $this->architectureFallback($goal);
        $skills = collect($analysis['skills'] ?? [])->map(function ($skill) use ($assessment) {
            $result = collect($assessment['skills'] ?? [])->firstWhere('name', $skill['name']);

            return ['name' => $skill['name'], 'importance' => $skill['importance'] ?? 'media', 'subskills' => $skill['subskills'] ?? [], 'measured_level' => $result['level'] ?? 'sin medir', 'score' => $result['score'] ?? null];
        })->values()->all();
        $guide = ($analysis['domain'] ?? null) === 'ofimatica'
            ? 'Como referencia de alcance para ofimática puedes apoyarte en este currículo, adaptándolo al nivel medido (no lo copies tal cual): '.json_encode(collect(config('office_curriculum.architecture.modules', []))->map(fn ($module) => ['fase' => $module['title'], 'temas' => array_column($module['topics'], 'title')])->all(), JSON_UNESCAPED_UNICODE).'. '
            : '';

        $prompt = 'Eres arquitecto curricular para empleabilidad en Guatemala. Diseña la ruta de aprendizaje de esta persona: solo la estructura de fases y temas.'."\n"
            .'Meta: '.json_encode($goal, JSON_UNESCAPED_UNICODE).'. Lo que entendimos: '.($analysis['summary'] ?? '').' Objetivo: '.($analysis['target_role'] ?? '').'.'."\n"
            .'Habilidades que exige la meta con el nivel medido en el diagnóstico: '.json_encode($skills, JSON_UNESCAPED_UNICODE)."\n"
            .'Nivel general medido: '.($assessment['level'] ?? 'Inicial').' ('.($assessment['score'] ?? 0).' de 100). Contexto declarado: '.json_encode($assessment['context'] ?? [], JSON_UNESCAPED_UNICODE).'.'."\n"
            .'Conocimientos previos que sugiere su texto: '.json_encode($analysis['prior_knowledge_signals'] ?? [], JSON_UNESCAPED_UNICODE).'. Restricciones: '.json_encode($analysis['constraints'] ?? [], JSON_UNESCAPED_UNICODE).'.'."\n"
            .$guide
            .'Reglas: de 5 a 8 fases y de 3 a 5 temas por fase, en orden de prerrequisitos. Empieza por las habilidades importantes con menor nivel medido; '
            .'lo que ya domina (nivel Avanzado) no se repite: como mucho, un tema de profundización. Cubre todas las habilidades que no estén en nivel Avanzado. '
            .'skill de cada fase es el nombre exacto de la habilidad que desarrolla. '
            .'Cada tema tiene un title concreto y un learning_objective observable y medible que empieza con un verbo y menciona la tarea laboral (por ejemplo "Calcular el total vendido por categoría con SUMAR.SI en un reporte semanal"), nunca "Aplicar X en una situación". '
            .'practice_type de cada tema, de '.implode(', ', self::PRACTICE_TYPES).': excel_dataset si se trabaja con datos en una hoja de cálculo, code_bug o code_complete si se programa, language_activity para idiomas, case_study para decisiones o procedimientos, theory_quiz solo para conceptos que no se pueden practicar de otra forma. Tipos recomendados para esta meta: '.json_encode($analysis['recommended_practice_types'] ?? [], JSON_UNESCAPED_UNICODE).'. '
            .'Cada fase tiene un quiz que desbloquea la siguiente: una pregunta sobre su contenido que solo responde quien lo aprendió, 3 opciones y answer con el índice (0 a 2) de la correcta. '
            .'total_hours realista según el tiempo semanal declarado. title es el nombre de la ruta; summary explica en 2 oraciones por qué empieza donde empieza. '
            .'No generes teoría, datos, código ni ejercicios. Español latinoamericano claro.';

        $review = 'Revisa esta ruta como diseñador curricular experto. Verifica que empiece por las brechas medidas más importantes, que respete los prerrequisitos, que no repita lo que la persona ya domina, que cubra todas las habilidades que no domina, que cada objetivo sea observable y laboral, que el tipo de práctica de cada tema sea el adecuado y que la respuesta marcada en cada quiz sea la única correcta. Corrige lo necesario y devuelve la ruta completa.';

        $missing = collect($skills)->reject(fn ($skill) => $skill['measured_level'] === 'Avanzado')->pluck('name')->all();

        return $this->generator->generate('learning_path', $prompt, $review, $this->architectureSchema(), 'learning_architecture', (int) config('ai.architecture_max_tokens'), $userId,
            fn (array $value) => $this->problems($value, $missing), fn () => $fallback);
    }

    /** @return array{0: array<int, string>, 1: array<int, string>} */
    private function problems(array $value, array $requiredSkills): array
    {
        [$hard, $soft] = [[], []];
        $modules = $value['modules'] ?? [];
        if (count($modules) < 5 || count($modules) > 8) {
            $hard[] = 'La ruta necesita entre 5 y 8 fases.';
        }
        foreach ($modules as $index => $module) {
            $topics = $module['topics'] ?? [];
            if (count($topics) < 3 || count($topics) > 5) {
                $hard[] = 'La fase '.($index + 1).' necesita entre 3 y 5 temas.';
            }
            foreach ($topics as $topic) {
                if (! in_array($topic['practice_type'] ?? null, self::PRACTICE_TYPES, true)) {
                    $hard[] = 'El tema "'.($topic['title'] ?? '').'" tiene un tipo de práctica no válido.';
                }
                if (preg_match('/^aplicar .* en una situaci/i', (string) ($topic['learning_objective'] ?? '')) || mb_strlen((string) ($topic['learning_objective'] ?? '')) < 30) {
                    $soft[] = 'El objetivo del tema "'.($topic['title'] ?? '').'" debe ser observable y mencionar la tarea laboral.';
                }
            }
            $quiz = $module['quiz'] ?? [];
            if (count($quiz['options'] ?? []) !== 3 || ! in_array($quiz['answer'] ?? null, [0, 1, 2], true)) {
                $hard[] = 'El quiz de la fase '.($index + 1).' necesita 3 opciones y una respuesta entre 0 y 2.';
            }
        }
        $covered = collect($modules)->map(fn ($module) => Str::lower(Str::ascii(trim((string) ($module['skill'] ?? '')))))->all();
        foreach ($requiredSkills as $skill) {
            if (! in_array(Str::lower(Str::ascii($skill)), $covered, true)) {
                $soft[] = 'Ninguna fase desarrolla la habilidad "'.$skill.'", que la persona aún no domina.';
            }
        }

        return [$hard, $soft];
    }

    private function architectureFallback(string $goal): array
    {
        $type = $this->skillTypeFor($goal);
        $blueprints = match ($type) {
            'programacion' => [
                ['Fundamentos de la web', ['Internet y navegador', 'HTML semántico', 'CSS básico']], ['Interfaz y diseño', ['Layout responsive', 'Accesibilidad', 'Componentes reutilizables']], ['JavaScript práctico', ['Variables y funciones', 'DOM y eventos', 'Consumo de datos']], ['Herramientas profesionales', ['Git y GitHub', 'Depuración', 'Despliegue']], ['Portafolio y empleo', ['Proyecto integrador', 'Documentación', 'Preparación de entrevistas']],
            ],
            'ofimatica' => [
                ['Fundamentos de Office', ['Archivos y organización', 'Atajos útiles', 'Formatos profesionales']], ['Hojas de cálculo', ['Fórmulas básicas', 'Funciones y referencias', 'Tablas y filtros']], ['Análisis de datos', ['Validación de datos', 'Tablas dinámicas', 'Gráficos útiles']], ['Documentos y presentaciones', ['Estilos y plantillas', 'Informes claros', 'Presentación visual']], ['Aplicación laboral', ['Caso de ventas', 'Reporte ejecutivo', 'Portafolio Office']],
            ],
            'idiomas' => [
                ['Base comunicativa', ['Vocabulario esencial', 'Pronunciación', 'Frases cotidianas']], ['Comprensión', ['Lectura guiada', 'Escucha activa', 'Ideas principales']], ['Producción', ['Gramática funcional', 'Escritura breve', 'Conversación']], ['Contexto laboral', ['Presentación personal', 'Correo profesional', 'Entrevista']], ['Fluidez aplicada', ['Situaciones reales', 'Retroalimentación', 'Plan de práctica']],
            ],
            default => [
                ['Fundamentos esenciales', ['Panorama del área', 'Vocabulario clave', 'Herramientas iniciales']], ['Técnicas principales', ['Proceso paso a paso', 'Buenas prácticas', 'Errores frecuentes']], ['Aplicación guiada', ['Caso básico', 'Toma de decisiones', 'Revisión de resultados']], ['Proyecto práctico', ['Planificación', 'Ejecución', 'Mejora']], ['Empleabilidad y progreso', ['Evidencia de habilidades', 'Comunicación profesional', 'Siguiente nivel']],
            ],
        };

        return [
            'level' => 'Inicial', 'title' => 'Ruta para '.$goal, 'summary' => 'Ruta progresiva con contenido y práctica bajo demanda.', 'total_hours' => 36,
            'modules' => collect($blueprints)->values()->map(function ($module, $index) use ($type) {
                return [
                    'title' => $module[0], 'description' => 'Desarrolla habilidades aplicables y medibles en esta etapa.', 'skill_type' => $type,
                    'topics' => collect($module[1])->values()->map(fn ($title, $topicIndex) => [
                        'title' => $title, 'learning_objective' => 'Aplicar '.$title.' en una situación relacionada con la meta del estudiante.',
                        'practice_type' => $this->practiceTypeFor($type, $index, $topicIndex),
                    ])->all(),
                ];
            })->all(),
        ];
    }

    private function skillTypeFor(string $goal): string
    {
        $goal = Str::lower($goal);
        if (Str::contains($goal, ['web', 'program', 'desarroll', 'software', 'código', 'codigo'])) {
            return 'programacion';
        }
        if (Str::contains($goal, ['excel', 'office', 'word', 'powerpoint', 'ofimática', 'ofimatica'])) {
            return 'ofimatica';
        }
        if (Str::contains($goal, ['inglés', 'ingles', 'idioma', 'english'])) {
            return 'idiomas';
        }
        if (Str::contains($goal, ['diseño', 'diseno', 'gráfico', 'grafico'])) {
            return 'diseno';
        }
        if (Str::contains($goal, ['matemática', 'matematica', 'profesor', 'docencia'])) {
            return 'pedagogia';
        }

        return 'general';
    }

    private function practiceTypeFor(string $skillType, int $module, int $topic): string
    {
        if ($skillType === 'ofimatica') {
            return $module >= 1 ? 'excel_dataset' : 'case_study';
        }
        if ($skillType === 'programacion') {
            return $module >= 2 ? ($topic % 2 ? 'code_complete' : 'code_bug') : 'theory_quiz';
        }
        if ($skillType === 'idiomas') {
            return 'language_activity';
        }

        return $module >= 2 ? 'case_study' : 'theory_quiz';
    }

    private function architectureSchema(): array
    {
        $string = ['type' => 'string'];
        $object = fn (array $properties) => ['type' => 'object', 'additionalProperties' => false, 'required' => array_keys($properties), 'properties' => $properties];

        return $object([
            'level' => $string,
            'title' => $string,
            'summary' => $string,
            'total_hours' => ['type' => 'integer', 'minimum' => 4, 'maximum' => 400],
            'modules' => ['type' => 'array', 'minItems' => 5, 'maxItems' => 8, 'items' => $object([
                'title' => $string,
                'description' => $string,
                'skill' => $string,
                'skill_type' => $string,
                'topics' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 5, 'items' => $object(['title' => $string, 'learning_objective' => $string, 'practice_type' => ['type' => 'string', 'enum' => self::PRACTICE_TYPES]])],
                'quiz' => $object(['question' => $string, 'options' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 3, 'items' => $string], 'answer' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 2]]),
            ])],
        ]);
    }
}
