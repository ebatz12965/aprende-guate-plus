<?php

namespace Tests\Feature;

use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MentorshipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_request_notifies_mentor_and_mentor_can_accept_with_automatic_message(): void
    {
        Role::findOrCreate('Instructor');
        $student = User::factory()->create(['name' => 'Estudiante Uno']);
        $mentor = User::factory()->create(['name' => 'Mentora Dos']);
        $mentor->assignRole('Instructor');
        NotificationPreference::create(['user_id' => $student->id, 'email_messages' => false, 'email_mentorship' => false, 'email_platform' => false]);
        NotificationPreference::create(['user_id' => $mentor->id, 'email_messages' => false, 'email_mentorship' => false, 'email_platform' => false]);
        MentorProfile::create(['user_id' => $mentor->id]);

        $this->actingAs($student)
            ->post(route('adaptive.mentorship.request', $mentor->mentorProfile), ['topic' => 'Tablas dinámicas en Excel'])
            ->assertSessionHasNoErrors();

        $session = MentorshipSession::firstOrFail();
        $this->assertSame(1, $mentor->notifications()->count());

        $this->actingAs($mentor)
            ->post(route('mentor.sessions.accept', $session))
            ->assertRedirect(route('mentor.sessions.show', $session));

        $session->refresh();
        $this->assertSame('accepted', $session->status);
        $this->assertNotNull($session->responded_at);
        $this->assertNotEmpty($session->decision_message);
        $this->assertTrue($session->messages()->firstOrFail()->is_automatic);
        $this->assertSame(1, $student->notifications()->count());
    }

    public function test_mentor_can_reject_respectfully_and_student_cannot_open_pending_conversation(): void
    {
        Role::findOrCreate('Instructor');
        $student = User::factory()->create();
        $mentor = User::factory()->create();
        $mentor->assignRole('Instructor');
        NotificationPreference::create(['user_id' => $student->id, 'email_messages' => false, 'email_mentorship' => false, 'email_platform' => false]);
        NotificationPreference::create(['user_id' => $mentor->id, 'email_messages' => false, 'email_mentorship' => false, 'email_platform' => false]);
        $session = MentorshipSession::create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'topic' => 'Diseño de redes',
        ]);

        $this->actingAs($student)->get(route('mentorship.sessions.show', $session))->assertForbidden();

        $this->actingAs($mentor)
            ->post(route('mentor.sessions.reject', $session), ['reason' => 'Mi especialidad es análisis de datos.'])
            ->assertRedirect(route('mentor.dashboard'));

        $this->assertSame('rejected', $session->fresh()->status);
        $this->assertStringContainsString('análisis de datos', $session->fresh()->decision_message);
        $this->assertSame(1, $student->notifications()->count());
    }
}