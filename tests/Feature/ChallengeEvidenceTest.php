<?php
namespace Tests\Feature;
use App\Jobs\EvaluateChallengeSubmissionJob;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ChallengeEvidenceTest extends TestCase {
    use RefreshDatabase;
    public function test_student_can_submit_detailed_evidence_with_a_private_attachment(): void {
        Queue::fake(); Storage::fake('local');
        $user = User::factory()->create();
        $challenge = Challenge::create(['title'=>'Prueba detallada','description'=>'Descripción','case_context'=>'Caso','objectives'=>['Objetivo'],'deliverables'=>[['name'=>'Archivo']],'requirements'=>['Requisito'],'rubric'=>[['name'=>'Criterio','weight'=>100,'description'=>'Detalle']]]);
        $this->actingAs($user)->post(route('adaptive.challenges.submit', $challenge), ['answer'=>'Hallazgo principal y recomendación.','methodology'=>'Importé el archivo, validé los datos y preparé el resumen.','attachment'=>UploadedFile::fake()->create('reporte.csv', 8, 'text/csv')])->assertRedirect(route('adaptive.challenges.show', $challenge));
        $this->assertDatabaseHas('challenge_submissions', ['challenge_id'=>$challenge->id,'user_id'=>$user->id,'status'=>'queued','attachment_name'=>'reporte.csv']);
        Queue::assertPushed(EvaluateChallengeSubmissionJob::class);
    }
    public function test_student_can_download_challenge_material(): void {
        $user = User::factory()->create();
        $challenge = Challenge::create(['title'=>'Prueba con material','description'=>'Descripción','resources'=>[['label'=>'Datos','filename'=>'datos.csv','mime'=>'text/csv; charset=UTF-8','content'=>"campo,valor\na,1"]]]);
        $this->actingAs($user)->get(route('adaptive.challenges.resource', [$challenge, 0]))->assertOk()->assertSee('campo,valor');
    }
}