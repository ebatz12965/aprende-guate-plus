<?php

namespace Tests\Feature;

use App\Models\DiagnosticAttempt;
use App\Models\LearningPath;
use App\Models\LearningPathTopic;
use App\Models\MentorshipSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationPoliciesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin'));

        return $admin;
    }

    private function topicOf(User $user, string $status = 'active'): LearningPathTopic
    {
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => 'Excel', 'status' => $status]);
        $module = $path->items()->create(['title' => 'Módulo', 'area' => 'Base', 'position' => 1, 'quiz' => ['question' => 'P', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);

        return $module->topics()->create(['title' => 'Tema', 'learning_objective' => 'Aplicar.', 'practice_type' => 'case_study', 'position' => 1]);
    }

    public function test_student_cannot_open_or_generate_content_for_another_students_topic(): void
    {
        $topic = $this->topicOf(User::factory()->create());
        $intruder = User::factory()->create();
        $this->topicOf($intruder);

        $this->actingAs($intruder)->get(route('adaptive.topic.show', $topic))->assertNotFound();
        $this->actingAs($intruder)->post(route('adaptive.topic.lesson', $topic))->assertNotFound();
        $this->actingAs($intruder)->getJson(route('adaptive.topic.generation.status', $topic))->assertNotFound();
    }

    public function test_topics_of_a_replaced_route_are_no_longer_accessible(): void
    {
        $student = User::factory()->create();
        $topic = $this->topicOf($student, 'replaced');

        $this->actingAs($student)->get(route('adaptive.topic.show', $topic))->assertNotFound();
    }

    public function test_admin_role_does_not_bypass_ownership_of_personal_data(): void
    {
        $student = User::factory()->create();
        $attempt = DiagnosticAttempt::create(['user_id' => $student->id, 'primary_answer' => 'Aprender Excel', 'questions' => [], 'answers' => [], 'status' => 'started']);
        $topic = $this->topicOf($student);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('adaptive.diagnostic.questions', $attempt))->assertNotFound();
        $this->actingAs($admin)->get(route('adaptive.topic.show', $topic))->assertNotFound();
    }

    public function test_only_participants_can_read_a_mentorship_conversation(): void
    {
        $student = User::factory()->create();
        $mentor = User::factory()->create();
        $session = MentorshipSession::create(['student_id' => $student->id, 'mentor_id' => $mentor->id, 'topic' => 'Excel', 'status' => 'accepted']);

        $this->actingAs($student)->getJson(route('mentorship.sessions.messages', $session))->assertOk();
        $this->actingAs(User::factory()->create())->getJson(route('mentorship.sessions.messages', $session))->assertForbidden();
        $this->actingAs($this->admin())->getJson(route('mentorship.sessions.messages', $session))->assertForbidden();
    }

    public function test_only_the_assigned_mentor_can_respond_to_a_request(): void
    {
        $instructor = Role::findOrCreate('Instructor');
        $mentor = User::factory()->create();
        $otherMentor = User::factory()->create();
        $mentor->assignRole($instructor);
        $otherMentor->assignRole($instructor);
        $session = MentorshipSession::create(['student_id' => User::factory()->create()->id, 'mentor_id' => $mentor->id, 'topic' => 'Excel']);

        $this->actingAs($otherMentor)->post(route('mentor.sessions.reject', $session))->assertForbidden();
        $this->assertSame('requested', $session->fresh()->status);
    }
}
