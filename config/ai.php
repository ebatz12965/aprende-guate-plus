<?php

// planned: el agente aún no tiene un módulo que lo invoque; no se puede activar desde administración.
$agent = static fn (string $name, string $description, int $maxTokens, bool $enabled = true, bool $planned = false): array => [
    'name' => $name,
    'description' => $description,
    'provider' => 'openai',
    'max_tokens' => $maxTokens,
    'enabled' => $enabled && ! $planned,
    'planned' => $planned,
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
    // Lecciones, prácticas y su revisión son respuestas largas.
    'content_timeout' => (int) env('AI_CONTENT_TIMEOUT', 75),
    // Segunda pasada que revisa y corrige cada lección y práctica antes de guardarla.
    'content_review' => (bool) env('AI_CONTENT_REVIEW', true),
    'tutor_timeout' => (int) env('AI_TUTOR_TIMEOUT', 20),
    'retry_times' => (int) env('AI_RETRY_TIMES', 1),
    'architecture_max_tokens' => (int) env('AI_ARCHITECTURE_MAX_TOKENS', 1200),
    'lesson_max_tokens' => (int) env('AI_LESSON_MAX_TOKENS', 3200),
    'practice_max_tokens' => (int) env('AI_PRACTICE_MAX_TOKENS', 2400),
    'evaluation_max_tokens' => (int) env('AI_EVALUATION_MAX_TOKENS', 900),
    'content_classifier_max_tokens' => (int) env('AI_CONTENT_CLASSIFIER_MAX_TOKENS', 550),
    'agents' => [
        'career_profiler' => $agent('Perfilador profesional', 'Convierte diagnóstico y objetivo en perfil, nivel y brechas de habilidades.', 600),
        'learning_path' => $agent('Diseñador de rutas', 'Crea una arquitectura de aprendizaje progresiva y dependiente de habilidades.', 1200),
        'curriculum' => $agent('Diseñador curricular', 'Desarrolla unidades, objetivos, prerrequisitos y estimaciones.', 1000, false, true),
        'content_classifier' => $agent('Clasificador de contenido', 'Decide el formato pedagógico, el tipo de práctica y los recursos que necesita cada tema.', 550),
        'theory_content' => $agent('Generador de teoría', 'Produce contenido estructurado, claro y pedagógico.', 3200),
        'tutorial_script' => $agent('Guionista tutorial', 'Produce guiones y escenas; no genera video directamente.', 900, false, true),
        'exercise_generator' => $agent('Generador de ejercicios', 'Crea prácticas profesionales, datasets y criterios de entrega.', 2400),
        'content_reviewer' => $agent('Revisor de contenido', 'Revisa y corrige lecciones y prácticas: exactitud, respuestas correctas, datos coherentes y claridad.', 3200),
        'file_generator' => $agent('Especificador de archivos', 'Describe archivos a generar por el backend; nunca produce binarios.', 600, false, true),
        'evaluator' => $agent('Evaluador de evidencias', 'Evalúa contra rúbricas y explica mejoras sin revelar soluciones.', 900),
        'tutor' => $agent('Tutor IA', 'Da pistas progresivas y orientación contextual al estudiante.', 300),
        'interviewer' => $agent('Entrevistador', 'Conduce entrevistas escritas por estados y preguntas adaptativas.', 700, false, true),
        'interview_evaluator' => $agent('Evaluador de entrevista', 'Califica entrevistas separadamente mediante rúbricas.', 900, false, true),
        'employability' => $agent('Agente de empleabilidad', 'Relaciona vacantes, perfil, evidencias y brechas.', 800, false, true),
        'mentor_application_assessor' => $agent('Generador de cuestionarios de mentoría', 'Genera un cuestionario pedagógico según la especialidad y las áreas de apoyo declaradas, sin evaluar ni recibir respuestas personales.', 900),
    ],
];
