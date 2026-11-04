<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use App\Support\Archivos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Archivos de usuarios y fotos servidos por la aplicación, estén en el servidor o en el bucket privado de S3. */
class ArchivosEnS3Test extends TestCase
{
    use RefreshDatabase;

    private function usarS3(): void
    {
        config(['filesystems.archivos' => 's3_archivos', 'filesystems.fotos' => 's3_archivos']);
        Storage::fake('s3_archivos');
    }

    public function test_los_archivos_se_guardan_en_el_disco_configurado(): void
    {
        $this->usarS3();
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('adaptive.career.update'), [
            'bio_visibility' => 'private',
            'education_visibility' => 'private',
            'work_experience_visibility' => 'private',
            'cv' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            'profile_photo' => UploadedFile::fake()->image('perfil.png', 300, 300),
        ])->assertSessionHasNoErrors();

        $profile = StudentProfile::where('user_id', $user->id)->firstOrFail();
        Storage::disk('s3_archivos')->assertExists([$profile->cv_path, $profile->photo_path]);
        $this->actingAs($user)->get(route('adaptive.career.cv'))->assertOk();
    }

    public function test_la_foto_se_sirve_al_duenio_y_en_el_perfil_publico_solo_si_esta_activo(): void
    {
        $this->usarS3();
        $user = User::factory()->create();
        Storage::disk('s3_archivos')->put('profile-photos/foto.png', 'imagen');
        $profile = StudentProfile::create(['user_id' => $user->id, 'photo_path' => 'profile-photos/foto.png', 'is_public' => false]);
        $profile->regeneratePublicSlug();
        $slug = $profile->fresh()->public_slug;

        $this->actingAs($user)->get(route('adaptive.career.photo'))->assertOk()->assertHeader('Cache-Control', 'max-age=86400, private');
        $this->get(route('adaptive.public-profile.photo', $slug))->assertNotFound();

        $profile->update(['is_public' => true]);
        $this->get(route('adaptive.public-profile.photo', $slug))->assertOk();
        $this->get(route('adaptive.public-profile', $slug))->assertSee(route('adaptive.public-profile.photo', $slug), false);
    }

    public function test_un_archivo_en_s3_se_lee_con_una_copia_temporal_que_luego_se_borra(): void
    {
        $this->usarS3();
        Storage::disk('s3_archivos')->put('challenge-evidence/1/nota.txt', 'Contenido de la evidencia');

        $rutaUsada = null;
        $texto = Archivos::conRutaLocal('challenge-evidence/1/nota.txt', function (string $ruta) use (&$rutaUsada) {
            $rutaUsada = $ruta;

            return file_get_contents($ruta);
        });

        $this->assertSame('Contenido de la evidencia', $texto);
        $this->assertFileDoesNotExist($rutaUsada);
        $this->assertNull(Archivos::conRutaLocal('no-existe.txt', fn () => 'nunca'));
    }
}
