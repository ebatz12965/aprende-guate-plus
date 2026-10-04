<?php

namespace Tests\Feature;

use App\AI\AgentCatalog;
use App\Jobs\EvaluateChallengeSubmissionJob;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetryAiEvaluationsTest extends TestCase
{
    use RefreshDatabase;

    private function submission(string $status): ChallengeSubmission
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => 'Excel', 'status' => 'active']);
        $challenge = Challenge::create(['user_id' => $user->id, 'learning_path_id' => $path->id, 'milestone' => 25, 'generation_status' => 'completed', 'title' => 'Reto', 'description' => 'Descripción']);

        return ChallengeSubmission::create(['challenge_id' => $challenge->id, 'user_id' => $user->id, 'answer' => 'Respuesta', 'methodology' => 'Proceso', 'status' => $status]);
    }

    public function test_only_submissions_waiting_for_the_evaluator_are_requeued_by_default(): void
    {
        Queue::fake();
        $waiting = $this->submission('requires_ai');
        $failed = $this->submission('evaluation_failed');
        $done = $this->submission('evaluated');

        $this->artisan('ai:retry-evaluations')->assertSuccessful();

        $this->assertSame('queued', $waiting->fresh()->status);
        $this->assertSame('evaluation_failed', $failed->fresh()->status);
        $this->assertSame('evaluated', $done->fresh()->status);
        Queue::assertPushed(EvaluateChallengeSubmissionJob::class, 1);
    }

    public function test_failed_evaluations_are_requeued_only_on_request(): void
    {
        Queue::fake();
        $failed = $this->submission('evaluation_failed');

        $this->artisan('ai:retry-evaluations', ['--include-failed' => true])->assertSuccessful();

        $this->assertSame('queued', $failed->fresh()->status);
        Queue::assertPushed(EvaluateChallengeSubmissionJob::class, fn ($job) => $job->submissionId === $failed->id);
    }

    public function test_nothing_is_requeued_while_the_evaluator_is_disabled(): void
    {
        Queue::fake();
        $waiting = $this->submission('requires_ai');
        app(AgentCatalog::class)->saveOverride('evaluator', false, null, null, null);

        $this->artisan('ai:retry-evaluations')->expectsOutputToContain('no está disponible')->assertSuccessful();

        $this->assertSame('requires_ai', $waiting->fresh()->status);
        Queue::assertNothingPushed();
    }
}
