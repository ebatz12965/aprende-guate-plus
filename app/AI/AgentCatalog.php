<?php

namespace App\AI;

class AgentCatalog
{
    public const AGENTS = ['career_profiler', 'learning_path', 'curriculum', 'content_classifier', 'theory_content', 'tutorial_script', 'exercise_generator', 'file_generator', 'evaluator', 'tutor', 'interviewer', 'interview_evaluator', 'employability', 'mentor_application_assessor'];

    public function configuration(string $slug): array
    {
        if (! in_array($slug, self::AGENTS, true)) {
            throw new \InvalidArgumentException("Agente IA no reconocido: {$slug}");
        }

        return config("ai.agents.{$slug}", []);
    }

    public function all(): array
    {
        return collect(self::AGENTS)->mapWithKeys(fn (string $slug) => [$slug => $this->configuration($slug)])->all();
    }
}
