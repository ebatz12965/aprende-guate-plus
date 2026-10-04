<?php

namespace App\Services\Learning;

use App\AI\AiGateway;
use Illuminate\Support\Str;

/** Diseña la estructura de módulos y temas de una ruta (agente learning_path). */
class LearningPathArchitect
{
    public const PRACTICE_TYPES = ['excel_dataset', 'code_bug', 'code_complete', 'case_study', 'language_activity', 'theory_quiz'];

    public function __construct(private readonly AiGateway $gateway) {}

    /** Solo diseña la estructura: nunca genera lecciones ni prácticas en esta llamada. */
    public function design(string $goal, array $questions, array $answers, ?int $userId = null): array
    {
        if (Str::contains(Str::lower($goal), ['office', 'excel', 'word', 'powerpoint', 'ofimática', 'ofimatica'])) {
            return config('office_curriculum.architecture');
        }
        $fallback = $this->architectureFallback($goal);
        $prompt = 'Eres arquitecto curricular para empleabilidad. Diseña únicamente el roadmap para la meta "'.$goal.'". '
            .'Usa el diagnóstico: '.json_encode(['questions' => $questions, 'answers' => $answers], JSON_UNESCAPED_UNICODE).'. '
            .'Genera entre 5 y 8 módulos progresivos; cada uno debe tener 3 a 5 temas concretos, objetivos laborales claros y un tipo de práctica permitido. '
            .'No generes teoría detallada, datasets, código ni ejercicios.';
        $result = $this->askStructured($prompt, $fallback, $this->architectureSchema(), 'learning_architecture', config('ai.architecture_max_tokens'), $userId);

        return $this->validArchitecture($result) ? $result : $fallback;
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

    private function validArchitecture(array $value): bool
    {
        return count($value['modules'] ?? []) >= 5 && count($value['modules'] ?? []) <= 8 && collect($value['modules'])->every(fn ($module) => count($module['topics'] ?? []) >= 3 && count($module['topics'] ?? []) <= 5 && collect($module['topics'])->every(fn ($topic) => in_array(data_get($topic, 'practice_type'), self::PRACTICE_TYPES, true)));
    }

    private function architectureSchema(): array
    {
        return ['type' => 'object', 'additionalProperties' => false, 'required' => ['level', 'title', 'summary', 'total_hours', 'modules'], 'properties' => ['level' => ['type' => 'string'], 'title' => ['type' => 'string'], 'summary' => ['type' => 'string'], 'total_hours' => ['type' => 'integer'], 'modules' => ['type' => 'array', 'minItems' => 5, 'maxItems' => 8, 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'description', 'skill_type', 'topics'], 'properties' => ['title' => ['type' => 'string'], 'description' => ['type' => 'string'], 'skill_type' => ['type' => 'string'], 'topics' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 5, 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'learning_objective', 'practice_type'], 'properties' => ['title' => ['type' => 'string'], 'learning_objective' => ['type' => 'string'], 'practice_type' => ['type' => 'string', 'enum' => self::PRACTICE_TYPES]]]]]]]]];
    }

    private function askStructured(string $prompt, array $fallback, array $schema, string $name, int $maxTokens, ?int $userId = null): array
    {
        return $this->gateway->structured('learning_path', $prompt, $schema, $name, $userId, $maxTokens) ?? $fallback;
    }
}
