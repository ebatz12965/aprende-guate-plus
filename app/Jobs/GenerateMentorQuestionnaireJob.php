<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Services\MentorQuestionnaireDraft;
use App\Services\MentorQuestionnaireService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateMentorQuestionnaireJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $userId, public string $title, public string $specialties)
    {
        $this->timeout = AiGateway::jobTimeout(1);
    }

    public function handle(MentorQuestionnaireService $questionnaire): void
    {
        MentorQuestionnaireDraft::complete($this->userId, $questionnaire->generate($this->title, $this->specialties, $this->userId));
    }

    /** Si el job falla, la persona continúa con el cuestionario genérico en lugar de quedarse esperando. */
    public function failed(?Throwable $exception): void
    {
        MentorQuestionnaireDraft::complete($this->userId, app(MentorQuestionnaireService::class)->fallbackQuestions());
    }
}
