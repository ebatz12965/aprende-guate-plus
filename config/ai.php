<?php

// planned: el agente aún no tiene un módulo que lo invoque; no se puede activar desde administración.
// task: reasoning (análisis y decisiones que deben ser correctas), writing (redacción larga) o fast (respuestas cortas).
$agent = static fn (string $name, string $description, int $maxTokens, bool $enabled = true, bool $planned = false, string $task = 'writing'): array => [
    'name' => $name,
    'description' => $description,
    'provider' => 'openai',
    'max_tokens' => $maxTokens,
    'enabled' => $enabled && ! $planned,
    'planned' => $planned,
    'task' => $task,
];

return [
    'default_provider' => env('AI_PROVIDER', 'openai'),
    // Compatibilidad temporal con instalaciones que ya usan estas tres variables.
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    /*
     * Proveedores disponibles. driver "openai" usa la API de chat compatible con OpenAI
     * (OpenAI, Gemini y DeepSeek la ofrecen); driver "anthropic" usa la API de Claude.
     * structured: "json_schema" (esquema estricto) o "json_object" (JSON libre; el esquema
     * va en las instrucciones y la respuesta se valida igual).
     * Un proveedor solo se puede elegir si tiene su API key en .env.
     */
    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'driver' => 'openai',
            'base_url' => env('OPENAI_BASE_URL', env('AI_BASE_URL', 'https://api.openai.com/v1')),
            'key' => env('OPENAI_API_KEY', env('AI_API_KEY')),
            'structured' => 'json_schema',
            'token_parameter' => 'max_completion_tokens',
            'enabled' => true,
            'key_env' => 'OPENAI_API_KEY',
            'keys_url' => 'https://platform.openai.com/api-keys',
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'driver' => 'openai',
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
            'key' => env('GEMINI_API_KEY'),
            'structured' => 'json_schema',
            'token_parameter' => 'max_tokens',
            'enabled' => true,
            'key_env' => 'GEMINI_API_KEY',
            'keys_url' => 'https://aistudio.google.com/apikey',
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'driver' => 'openai',
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'key' => env('DEEPSEEK_API_KEY'),
            'structured' => 'json_object',
            'token_parameter' => 'max_tokens',
            'enabled' => true,
            'key_env' => 'DEEPSEEK_API_KEY',
            'keys_url' => 'https://platform.deepseek.com/api_keys',
        ],
        'anthropic' => [
            'name' => 'Anthropic (Claude)',
            'driver' => 'anthropic',
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'key' => env('ANTHROPIC_API_KEY'),
            'structured' => 'tool',
            'enabled' => true,
            'key_env' => 'ANTHROPIC_API_KEY',
            'keys_url' => 'https://console.anthropic.com/settings/keys',
        ],
    ],
    // Tiempo que se guarda la lista de modelos que devuelve cada proveedor.
    'models_cache_minutes' => (int) env('AI_MODELS_CACHE_MINUTES', 360),
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 5),
    'generation_timeout' => (int) env('AI_GENERATION_TIMEOUT', 30),
    // Lecciones, prácticas y su revisión son respuestas largas.
    'content_timeout' => (int) env('AI_CONTENT_TIMEOUT', 75),
    // Segunda pasada que revisa y corrige cada lección y práctica antes de guardarla.
    'content_review' => (bool) env('AI_CONTENT_REVIEW', true),
    'tutor_timeout' => (int) env('AI_TUTOR_TIMEOUT', 20),
    'retry_times' => (int) env('AI_RETRY_TIMES', 1),
    'architecture_max_tokens' => (int) env('AI_ARCHITECTURE_MAX_TOKENS', 5000),
    'analysis_max_tokens' => (int) env('AI_ANALYSIS_MAX_TOKENS', 2500),
    'diagnostic_max_tokens' => (int) env('AI_DIAGNOSTIC_MAX_TOKENS', 5000),
    // Simulaciones de entrevista que una persona puede crear por día (cada una consume varias llamadas a la IA).
    'interview_daily_limit' => (int) env('AI_INTERVIEW_DAILY_LIMIT', 3),
    'lesson_max_tokens' => (int) env('AI_LESSON_MAX_TOKENS', 3200),
    'practice_max_tokens' => (int) env('AI_PRACTICE_MAX_TOKENS', 2400),
    'evaluation_max_tokens' => (int) env('AI_EVALUATION_MAX_TOKENS', 900),
    'content_classifier_max_tokens' => (int) env('AI_CONTENT_CLASSIFIER_MAX_TOKENS', 550),
    'agents' => [
        'goal_analyst' => $agent('Analista de metas', 'Analiza a fondo la meta de la persona: puesto, habilidades que exige, conocimientos previos que sugiere y ambigüedades.', 2500, true, false, 'reasoning'),
        'diagnostic_designer' => $agent('Diseñador del diagnóstico', 'Crea el cuestionario de nivel con respuestas correctas por habilidad y dificultad, a partir del análisis de la meta.', 4000, true, false, 'reasoning'),
        // Reemplazado por goal_analyst y el cuestionario calificado; se conserva sin uso para no perder su historial.
        'career_profiler' => $agent('Perfilador profesional', 'Reemplazado por el Analista de metas y el cuestionario de nivel.', 600, false, true, 'reasoning'),
        'learning_path' => $agent('Diseñador de rutas', 'Crea una arquitectura de aprendizaje progresiva y dependiente de habilidades.', 5000, true, false, 'reasoning'),
        'curriculum' => $agent('Diseñador curricular', 'Desarrolla unidades, objetivos, prerrequisitos y estimaciones.', 1000, false, true),
        'content_classifier' => $agent('Clasificador de contenido', 'Decide el formato pedagógico, el tipo de práctica y los recursos que necesita cada tema.', 550, true, false, 'fast'),
        'theory_content' => $agent('Generador de teoría', 'Produce contenido estructurado, claro y pedagógico.', 3200, true, false, 'writing'),
        'tutorial_script' => $agent('Guionista tutorial', 'Produce guiones y escenas; no genera video directamente.', 900, false, true),
        'exercise_generator' => $agent('Generador de ejercicios', 'Crea prácticas profesionales, datasets y criterios de entrega.', 2400, true, false, 'writing'),
        'content_reviewer' => $agent('Revisor de contenido', 'Revisa y corrige lecciones y prácticas: exactitud, respuestas correctas, datos coherentes y claridad.', 3200, true, false, 'reasoning'),
        'file_generator' => $agent('Especificador de archivos', 'Describe archivos a generar por el backend; nunca produce binarios.', 600, false, true),
        'evaluator' => $agent('Evaluador de evidencias', 'Evalúa contra rúbricas y explica mejoras sin revelar soluciones.', 900, true, false, 'reasoning'),
        'tutor' => $agent('Tutor IA', 'Da pistas progresivas y orientación contextual al estudiante.', 300, true, false, 'fast'),
        'interviewer' => $agent('Entrevistador', 'Diseña el proceso de selección simulado y conduce entrevistas escritas con repreguntas.', 1400, true, false, 'writing'),
        'interview_evaluator' => $agent('Evaluador de entrevista', 'Califica las entrevistas simuladas con una rúbrica y da retroalimentación por pregunta.', 2200, true, false, 'reasoning'),
        'employability' => $agent('Agente de empleabilidad', 'Relaciona vacantes, perfil, evidencias y brechas.', 800, false, true),
        'mentor_application_assessor' => $agent('Generador de cuestionarios de mentoría', 'Genera un cuestionario pedagógico según la especialidad y las áreas de apoyo declaradas, sin evaluar ni recibir respuestas personales.', 900, true, false, 'writing'),
    ],
];
