<?php

return [
    'base_url' => env('AI_BASE_URL', 'https://openrouter.ai/api/v1'),
    'key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL', 'openai/gpt-4o-mini'),
];
