<?php

namespace Tests\Feature;

use App\Models\MentorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_can_generate_open_and_submit_the_questionnaire(): void
    {
        $user = User::factory()->create();

        // Sin respuesta válida del proveedor se usan las 20 preguntas de respaldo.
        $this->actingAs($user)->post(route('mentor-application.questionnaire'), ['professional_title' => 'Excel', 'specialties' => 'Tablas dinámicas'])
            ->assertRedirect(route('mentor-application.create'));
        $this->actingAs($user)->get(route('mentor-application.create'))->assertOk()->assertSee('answers[answer_1]', false);

        $answers = collect(range(1, 20))->mapWithKeys(fn ($i) => ["answer_{$i}" => str_repeat("Respuesta detallada número {$i}. ", 3)])->all();
        $this->actingAs($user)->post(route('mentor-application.store'), ['professional_title' => 'Excel', 'specialties' => 'Tablas dinámicas', 'answers' => $answers])
            ->assertRedirect(route('mentor-application.create'));

        $application = MentorApplication::firstOrFail();
        $this->assertSame('pending_review', $application->status);
        $this->assertCount(20, $application->questionnaire_answers);
    }
}
