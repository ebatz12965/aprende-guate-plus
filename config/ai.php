<?php

$agent = static fn (string $name, string $description, int $maxTokens, bool $enabled = true): array => [
    'name' => $name,
    'description' => $description,
    'provider' => 'openai',
    'max_tokens' => $maxTokens,
    'enabled' => $enabled,
];

return [
    'default_provider' => env('AI_PROVIDER', 'openai'),
    // Compatibilidad temporal con instalaciones que ya usan estas tres variables.
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'base_url' => env('OPENAI_BASE_URL', env('AI_BASE_URL', 'https://api.openai.com/v1')),
            'key' => env('OPENAI_API_KEY', env('AI_API_KEY')),
            'enabled' => true,
        ],
    ],
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 5),
    'generation_timeout' => (int) env('AI_GENERATION_TIMEOUT', 30),
    'tutor_timeout' => (int) env('AI_TUTOR_TIMEOUT', 20),
    'retry_times' => (int) env('AI_RETRY_TIMES', 1),
    'architecture_max_tokens' => (int) env('AI_ARCHITECTURE_MAX_TOKENS', 1200),
    'lesson_max_tokens' => (int) env('AI_LESSON_MAX_TOKENS', 800),
    'practice_max_tokens' => (int) env('AI_PRACTICE_MAX_TOKENS', 1000),
    'agents' => [
        'career_profiler' => $agent('Perfilador profesional', 'Convierte diagnóstico y objetivo en perfil, nivel y brechas de habilidades.', 600),
        'learning_path' => $agent('Diseñador de rutas', 'Crea una arquitectura de aprendizaje progresiva y dependiente de habilidades.', 1200),
        'curriculum' => $agent('Diseñador curricular', 'Desarrolla unidades, objetivos, prerrequisitos y estimaciones.', 1000),
        'theory_content' => $agent('Generador de teoría', 'Produce contenido estructurado, claro y pedagógico.', 900),
        'tutorial_script' => $agent('Guionista tutorial', 'Produce guiones y escenas; no genera video directamente.', 900),
        'exercise_generator' => $agent('Generador de ejercicios', 'Crea prácticas profesionales, datasets y criterios de entrega.', 1000),
        'file_generator' => $agent('Especificador de archivos', 'Describe archivos a generar por el backend; nunca produce binarios.', 600),
        'evaluator' => $agent('Evaluador de evidencias', 'Evalúa contra rúbricas y explica mejoras sin revelar soluciones.', 800),
        'tutor' => $agent('Tutor IA', 'Da pistas progresivas y orientación contextual al estudiante.', 300),
        'interviewer' => $agent('Entrevistador', 'Conduce entrevistas escritas por estados y preguntas adaptativas.', 700, false),
        'interview_evaluator' => $agent('Evaluador de entrevista', 'Califica entrevistas separadamente mediante rúbricas.', 900, false),
        'employability' => $agent('Agente de empleabilidad', 'Relaciona vacantes, perfil, evidencias y brechas.', 800, false),
    ],
];