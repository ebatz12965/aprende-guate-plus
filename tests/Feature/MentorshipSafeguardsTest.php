<?php

namespace Tests\Feature;

use App\Models\MentorApplication;
use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\MentorQuestionnaireDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MentorshipSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private function mentor(string $name = 'Mentora'): User
    {
        $mentor = User::factory()->create(['name' => $name]);
        $mentor->assignRole(Role::findOrCreate('Instructor'));
        NotificationPreference::create(['user_id' => $mentor->id, 'email_messages' => false, 'email_mentorship' => false, 'email_platform' => false]);
        MentorProfile::create(['user_id' => $mentor->id]);

        return $mentor;
    }

    private function application(User $user, string $status, ?string $notes = null): MentorApplication
    {
        return MentorApplication::create([
            'user_id' => $user->id, 'status' => $status, 'professional_title' => 'Excel', 'specialties' => 'Tablas dinámicas',
            'years_experience' => 0, 'professional_background' => 'Experiencia', 'teaching_approach' => 'Enfoque',
            'motivation' => 'Motivación', 'questionnaire_answers' => [], 'admin_notes' => $notes,
        ]);
    }

    public function test_admin_visiting_the_mentor_panel_is_not_listed_as_a_mentor(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Curioso']);
        $admin->assignRole(Role::findOrCreate('Admin'));
        $this->mentor('Mentora Real');
        $student = User::factory()->create();

        $this->actingAs($admin)->get(route('mentor.dashboard'))->assertOk();

        $this->assertDatabaseMissing('mentor_profiles', ['user_id' => $admin->id]);
        $this->actingAs($student)->get(route('adaptive.mentorship'))
            ->assertOk()
            ->assertSee('Mentora Real')
            ->assertDontSee('Admin Curioso');
    }

    public function test_profiles_without_the_instructor_role_cannot_receive_requests(): void
    {
        $former = User::factory()->create();
        $profile = MentorProfile::create(['user_id' => $former->id]);
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('adaptive.mentorship.request', $profile), ['topic' => 'Excel'])->assertNotFound();
        $this->assertSame(0, MentorshipSession::count());
    }

    public function test_mentor_cannot_request_a_session_from_themselves(): void
    {
        $mentor = $this->mentor();

        $this->actingAs($mentor)->post(route('adaptive.mentorship.request', $mentor->mentorProfile), ['topic' => 'Excel'])
            ->assertSessionHas('error');
        $this->assertSame(0, MentorshipSession::count());
    }

    public function test_student_cannot_open_duplicate_requests_with_the_same_mentor(): void
    {
        $mentor = $this->mentor();
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('adaptive.mentorship.request', $mentor->mentorProfile), ['topic' => 'Excel'])->assertSessionHasNoErrors();
        $this->actingAs($student)->post(route('adaptive.mentorship.request', $mentor->mentorProfile), ['topic' => 'Excel otra vez'])->assertSessionHas('error');

        $this->assertSame(1, MentorshipSession::count());

        MentorshipSession::first()->update(['status' => 'rejected']);
        $this->actingAs($student)->post(route('adaptive.mentorship.request', $mentor->mentorProfile), ['topic' => 'Nuevo intento'])->assertSessionMissing('error');
        $this->assertSame(2, MentorshipSession::count());
    }

    public function test_pending_mentor_application_blocks_a_second_one(): void
    {
        $user = User::factory()->create();
        $this->application($user, 'pending_review');

        $this->actingAs($user)->post(route('mentor-application.questionnaire'), ['professional_title' => 'Excel', 'specialties' => 'Fórmulas'])
            ->assertRedirect(route('mentor-application.create'))
            ->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_rejected_applicant_can_see_the_notes_and_apply_again(): void
    {
        $user = User::factory()->create();
        $this->application($user, 'rejected', 'Faltan ejemplos concretos de enseñanza.');

        $this->actingAs($user)->get(route('mentor-application.create'))
            ->assertOk()
            ->assertSee('Faltan ejemplos concretos de enseñanza.')
            ->assertSee(route('mentor-application.questionnaire'), false);
    }

    public function test_questionnaire_schema_is_strict_compatible_and_uses_ai_questions(): void
    {
        $questions = collect(range(1, 20))->map(fn ($i) => ['key' => "q{$i}", 'title' => "Bloque {$i}", 'prompt' => "Pregunta generada {$i}"])->all();
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode(['questions' => $questions])]]]])]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('mentor-application.questionnaire'), ['professional_title' => 'Excel', 'specialties' => 'Fórmulas'])
            ->assertRedirect(route('mentor-application.create'));

        $stored = MentorQuestionnaireDraft::get($user->id);
        $this->assertSame('ready', $stored['status']);
        $this->assertCount(20, $stored['questions']);
        $this->assertSame('Pregunta generada 1', $stored['questions'][0]['prompt']);

        Http::assertSent(function ($request) {
            $schema = $request['response_format']['json_schema']['schema'] ?? [];

            return ($request['response_format']['json_schema']['strict'] ?? false) === true
                && ($schema['additionalProperties'] ?? null) === false
                && ($schema['properties']['questions']['items']['additionalProperties'] ?? null) === false;
        });
    }

    public function test_admin_approval_adds_the_instructor_role_without_removing_existing_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin'));
        $applicant = User::factory()->create();
        $applicant->assignRole(Role::findOrCreate('Student'));
        $application = $this->application($applicant, 'pending_review');

        $this->actingAs($admin)->post(route('admin.mentor-applications.approve', $application), ['admin_notes' => 'Perfil sólido.'])
            ->assertRedirect(route('admin.mentor-applications.index'));

        $applicant->refresh();
        $this->assertTrue($applicant->hasRole('Instructor'));
        $this->assertTrue($applicant->hasRole('Student'));
        $this->assertDatabaseHas('mentor_profiles', ['user_id' => $applicant->id, 'headline' => 'Excel']);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame(1, $applicant->notifications()->count());
    }

    public function test_admin_rejection_requires_notes_and_reviewed_applications_cannot_change(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin'));
        $application = $this->application(User::factory()->create(), 'pending_review');

        $this->actingAs($admin)->post(route('admin.mentor-applications.reject', $application), ['admin_notes' => ''])->assertSessionHasErrors('admin_notes');
        $this->actingAs($admin)->post(route('admin.mentor-applications.reject', $application), ['admin_notes' => 'Faltan ejemplos de enseñanza.'])->assertRedirect();
        $this->assertSame('rejected', $application->fresh()->status);

        $this->actingAs($admin)->post(route('admin.mentor-applications.approve', $application))->assertStatus(422);
        $this->assertFalse($application->user->fresh()->hasRole('Instructor'));
    }
}
