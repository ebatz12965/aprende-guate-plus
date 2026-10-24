<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\Learning\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $name = 'María Fernanda López Cux'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('Student');

        return $user;
    }

    private function pathWithLastPhasePending(User $user): LearningPath
    {
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Excel para análisis de datos', 'goal' => 'Analizar ventas con Excel', 'estimated_level' => 'Inicial', 'estimated_hours' => 32, 'status' => 'active']);
        foreach ([1, 2] as $position) {
            $item = $path->items()->create(['title' => "Fase {$position}", 'position' => $position, 'status' => $position === 1 ? 'completed' : 'in_progress',
                'quiz' => ['question' => '¿Listo?', 'options' => ['Sí', 'No', 'Quizá'], 'answer' => 0]]);
            $item->topics()->create(['title' => "Tema {$position}", 'learning_objective' => 'Aplicar', 'practice_type' => 'case_study', 'position' => 1, 'status' => 'completed']);
        }

        return $path;
    }

    private function completed(User $user, string $title = 'Atención al cliente'): LearningPath
    {
        $path = LearningPath::create(['user_id' => $user->id, 'title' => $title, 'goal' => 'Atender clientes', 'estimated_level' => 'Intermedio', 'estimated_hours' => 24, 'status' => 'completed', 'completed_at' => now()->subDays(3)]);
        $path->items()->create(['title' => 'Comunicación', 'position' => 1, 'status' => 'completed', 'quiz' => ['question' => 'Q', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);

        return $path;
    }

    public function test_completing_the_last_phase_issues_the_certificate(): void
    {
        $user = $this->student();
        $path = $this->pathWithLastPhasePending($user);
        $last = $path->items()->where('position', 2)->first();

        $response = $this->actingAs($user)->post(route('adaptive.path.item.quiz', $last), ['answer' => 0]);

        $certificate = Certificate::sole();
        $response->assertRedirect(route('adaptive.certificates.show', $certificate));
        $this->assertSame('completed', $path->fresh()->status);
        $this->assertNotNull($path->fresh()->completed_at);
        $this->assertSame(['María Fernanda López Cux', 'Excel para análisis de datos', 2, 2, 32, 'Edwing Batz', 'CEO de Aprende Guate'],
            [$certificate->recipient_name, $certificate->title, $certificate->phases, $certificate->topics, $certificate->hours, $certificate->signer_name, $certificate->signer_title]);
        $this->assertMatchesRegularExpression('/^AG-\d{4}-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}$/', $certificate->code);
        // Volver a emitir no duplica el certificado.
        $this->assertTrue(app(CertificateService::class)->issue($path)->is($certificate));
        $this->assertSame(1, Certificate::count());
    }

    public function test_my_certificates_lists_completed_paths_and_issues_missing_ones(): void
    {
        $user = $this->student();
        // Una ruta terminada antes de existir los certificados recibe el suyo al abrir la sección.
        $this->completed($user, 'Atención al cliente y comunicación profesional');
        LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta en curso', 'status' => 'active']);

        $this->actingAs($user)->get(route('adaptive.certificates'))->assertOk()
            ->assertSee('Rutas completadas')->assertSee('Atención al cliente y comunicación profesional')
            ->assertSee('Descargar PDF')->assertSee('Certificado de finalización')->assertDontSee('Ruta en curso');
        $this->assertSame(1, Certificate::where('user_id', $user->id)->count());
    }

    public function test_without_completed_paths_it_shows_the_progress_of_the_current_one(): void
    {
        $user = $this->student();
        $this->pathWithLastPhasePending($user);

        $this->actingAs($user)->get(route('adaptive.certificates'))->assertOk()
            ->assertSee('Todavía no completas una ruta')->assertSee('Vas en 1 de 2 fases');
    }

    public function test_the_certificate_downloads_as_a_landscape_pdf_only_for_its_owner(): void
    {
        $user = $this->student();
        $certificate = app(CertificateService::class)->issue($this->completed($user));

        $response = $this->actingAs($user)->get(route('adaptive.certificates.pdf', $certificate));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString($certificate->code.'.pdf', (string) $response->headers->get('Content-Disposition'));
        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        // A4 horizontal: 841.89 × 595.28 puntos.
        $this->assertMatchesRegularExpression('/MediaBox \[0(\.0+)? 0(\.0+)? 841\.890? 595\.280?\]/', $pdf);

        $this->actingAs($this->student('Otra Persona'))->get(route('adaptive.certificates.pdf', $certificate))->assertNotFound();
        $this->actingAs($this->student('Otra Persona'))->get(route('adaptive.certificates.show', $certificate))->assertNotFound();
    }

    public function test_anyone_can_verify_a_certificate_with_its_code(): void
    {
        $user = $this->student();
        $certificate = app(CertificateService::class)->issue($this->completed($user));
        // El certificado conserva los datos de cuando se emitió.
        $user->update(['name' => 'Nombre Cambiado']);

        $this->get(route('certificates.verify', $certificate))->assertOk()
            ->assertSee('Certificado auténtico')->assertSee('María Fernanda López Cux')->assertSee($certificate->code)->assertDontSee('Nombre Cambiado')->assertDontSee($user->email);
        $this->get('/certificados/AG-2026-XXXX-XXXX')->assertNotFound();
    }

    public function test_the_demo_command_creates_completed_paths_with_certificates(): void
    {
        $user = $this->student('Demo Office');

        $this->artisan('certificates:demo', ['email' => $user->email])->assertSuccessful();
        $this->artisan('certificates:demo', ['email' => $user->email])->assertSuccessful();

        $this->assertSame(3, LearningPath::where('user_id', $user->id)->where('status', 'completed')->count());
        $this->assertSame(3, Certificate::where('user_id', $user->id)->count());
        $this->assertSame(['Fundamentos de soporte técnico de TI', 5, 11], [Certificate::latest('issued_at')->first()->title, Certificate::latest('issued_at')->first()->phases, Certificate::latest('issued_at')->first()->topics]);

        $this->artisan('certificates:demo', ['email' => $user->email, '--remove' => true])->assertSuccessful();
        $this->assertSame(0, Certificate::count());
    }
}
