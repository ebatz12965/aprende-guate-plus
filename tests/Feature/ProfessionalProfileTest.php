<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfessionalProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_save_professional_sections_and_visibility(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('adaptive.career.update'), [
            'professional_goal' => 'Analista de datos junior',
            'linkedin_url' => 'https://www.linkedin.com/in/test-user',
            'bio' => 'Me interesan los datos y la automatización.',
            'bio_visibility' => 'public',
            'education_visibility' => 'private',
            'education' => [[
                'institution' => 'Universidad de prueba',
                'program' => 'Ingeniería en Sistemas',
                'period' => '2022 - Actual',
            ]],
            'work_experience_visibility' => 'public',
            'work_experience' => [[
                'role' => 'Asistente de datos',
                'company' => 'Empresa de prueba',
                'period' => '2025 - Actual',
                'description' => 'Elaboración de reportes.',
            ]],
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $profile = StudentProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('public', $profile->bio_visibility);
        $this->assertSame('private', $profile->education_visibility);
        $this->assertSame('Ingeniería en Sistemas', $profile->education[0]['program']);
        $this->assertSame('Asistente de datos', $profile->work_experience[0]['role']);
    }

    public function test_student_can_upload_and_remove_a_professional_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $upload = $this->actingAs($user)->patch(route('adaptive.career.update'), [
            'bio_visibility' => 'private',
            'education_visibility' => 'private',
            'work_experience_visibility' => 'private',
            'profile_photo' => UploadedFile::fake()->image('perfil.png', 500, 500),
        ]);

        $upload->assertSessionHasNoErrors()->assertRedirect();

        $profile = StudentProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($profile->photo_path);
        Storage::disk('public')->assertExists($profile->photo_path);

        $remove = $this->actingAs($user)->patch(route('adaptive.career.update'), [
            'bio_visibility' => 'private',
            'education_visibility' => 'private',
            'work_experience_visibility' => 'private',
            'remove_photo' => true,
        ]);

        $remove->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($profile->fresh()->photo_path);
        Storage::disk('public')->assertMissing($profile->photo_path);
    }

    public function test_public_profile_hides_private_sections_and_shows_completed_paths(): void
    {
        $user = User::factory()->create(['name' => 'Perfil Público']);
        StudentProfile::create([
            'user_id' => $user->id,
            'bio' => 'Información privada',
            'bio_visibility' => 'private',
            'education' => [['institution' => 'Universidad pública', 'program' => 'Carrera visible', 'period' => '2026']],
            'education_visibility' => 'public',
            'work_experience_visibility' => 'private',
        ]);
        LearningPath::create([
            'user_id' => $user->id,
            'title' => 'Excel aplicado',
            'goal' => 'Mejorar reportes',
            'status' => 'completed',
        ]);

        $response = $this->get(route('adaptive.public-profile', $user));

        $response->assertOk()
            ->assertSee('Carrera visible')
            ->assertSee('Excel aplicado')
            ->assertDontSee('Información privada');
    }
}
