<?php

namespace Tests\Feature;

use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(['Admin', 'Student']);

        return $user;
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        return $user;
    }

    private function setModule(string $key, string $status, array $extra = []): void
    {
        $this->actingAs($this->superAdmin())->put(route('admin.modules.update', $key), ['status' => $status] + $extra)->assertSessionHasNoErrors();
        app(Modules::class)->flush();
    }

    public function test_tutor_in_maintenance_shows_the_notice_and_blocks_messages(): void
    {
        Http::fake();
        $this->setModule('tutor', 'maintenance', ['message' => 'Estamos actualizando el tutor.', 'until' => now()->addHours(2)->format('Y-m-d H:i')]);
        $student = $this->student();

        $this->actingAs($student)->get(route('adaptive.tutor'))->assertStatus(503)->assertSee('Estamos actualizando el tutor.')->assertSee('Volverá a estar disponible');
        $this->actingAs($student)->postJson(route('adaptive.tutor.message'), ['message' => 'Hola'])->assertStatus(503);
        Http::assertNothingSent();

        // El menú lo sigue mostrando, con la etiqueta, y el panel ya no ofrece la tarjeta del tutor.
        $this->actingAs($student)->get(route('adaptive.dashboard'))->assertOk()->assertSee('En mantenimiento')->assertDontSee('Preguntar al Tutor IA');
        // El super administrador puede entrar a revisarlo, con un recordatorio.
        $this->actingAs($this->superAdmin())->get(route('adaptive.tutor'))->assertOk()->assertSee('lo ves porque eres super administrador');
    }

    public function test_maintenance_ends_on_its_own_at_the_chosen_time(): void
    {
        $this->setModule('retos', 'maintenance', ['until' => now()->addHour()->format('Y-m-d H:i')]);
        $student = $this->student();
        $this->actingAs($student)->get(route('adaptive.challenges'))->assertStatus(503);

        $this->travel(61)->minutes();
        app(Modules::class)->flush();
        $this->assertSame(Modules::ACTIVE, app(Modules::class)->state('retos')['status']);
        $this->actingAs($student)->get(route('adaptive.challenges'))->assertOk();
    }

    public function test_preview_hides_the_module_except_for_chosen_roles(): void
    {
        Role::findOrCreate('Probadores', 'web');
        $this->setModule('mentoria', 'preview', ['preview_roles' => ['Probadores']]);

        $this->actingAs($this->student())->get(route('adaptive.mentorship'))->assertNotFound();
        $this->actingAs($this->student())->get(route('adaptive.dashboard'))->assertDontSee('Mentoría profesional');

        $tester = $this->student();
        $tester->assignRole('Probadores');
        $this->actingAs($tester)->get(route('adaptive.mentorship'))->assertOk();
    }

    public function test_interviews_start_in_preview_for_the_super_admin_only(): void
    {
        $modules = app(Modules::class);
        $this->assertSame(Modules::PREVIEW, $modules->state('entrevistas')['status']);
        $this->assertSame('hidden', $modules->access($this->student(), 'entrevistas'));
        $this->assertSame('allowed', $modules->access($this->superAdmin(), 'entrevistas'));
    }

    public function test_form_posts_are_bounced_with_the_notice_while_in_maintenance(): void
    {
        $this->setModule('mentoria', 'maintenance');
        $student = $this->student();
        $mentor = $this->student();
        $mentor->assignRole('Instructor');
        $profile = MentorProfile::create(['user_id' => $mentor->id, 'session_duration_minutes' => 30]);

        $this->actingAs($student)->from(route('adaptive.dashboard'))->post(route('adaptive.mentorship.request', $profile), ['topic' => 'CV'])
            ->assertRedirect(route('adaptive.dashboard'))->assertSessionHas('error');
        $this->assertSame(0, MentorshipSession::count());
        // El espacio de mentor también queda en mantenimiento.
        $this->actingAs($mentor)->get(route('mentor.dashboard'))->assertStatus(503);
    }

    public function test_only_the_super_admin_manages_modules_and_changes_are_logged(): void
    {
        $manager = $this->student();
        $manager->assignRole('Administrador');
        $this->actingAs($manager)->get(route('admin.modules.index'))->assertForbidden();
        $this->actingAs($manager)->put(route('admin.modules.update', 'tutor'), ['status' => 'maintenance'])->assertForbidden();

        $admin = $this->superAdmin();
        $this->actingAs($admin)->get(route('admin.modules.index'))->assertOk()->assertSee('Tutor IA')->assertSee('Entrevistas');
        $this->actingAs($admin)->put(route('admin.modules.update', 'tutor'), ['status' => 'maintenance', 'message' => 'Ajustes al tutor'])->assertRedirect();
        $this->actingAs($admin)->put(route('admin.modules.update', 'no-existe'), ['status' => 'active'])->assertNotFound();
        $this->actingAs($admin)->put(route('admin.modules.update', 'tutor'), ['status' => 'preview', 'preview_roles' => ['Admin']])->assertSessionHasErrors('preview_roles.0');

        $entry = Activity::where('log_name', 'configuracion')->latest('id')->first();
        $this->assertSame('Puso en mantenimiento el módulo «Tutor IA»', $entry->description);
        $this->assertSame('Ajustes al tutor', $entry->properties['mensaje']);
    }
}
