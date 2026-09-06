<?php

return [
    'base_url' => env('AI_BASE_URL', 'https://openrouter.ai/api/v1'),
    'key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL', 'openai/gpt-4o-mini'),
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 5),
    'generation_timeout' => (int) env('AI_GENERATION_TIMEOUT', 30),
    'tutor_timeout' => (int) env('AI_TUTOR_TIMEOUT', 20),
    'retry_times' => (int) env('AI_RETRY_TIMES', 1),
    'architecture_max_tokens' => (int) env('AI_ARCHITECTURE_MAX_TOKENS', 1200),
    'lesson_max_tokens' => (int) env('AI_LESSON_MAX_TOKENS', 800),
    'practice_max_tokens' => (int) env('AI_PRACTICE_MAX_TOKENS', 1000),
];
