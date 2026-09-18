<?php
namespace Tests\Feature;
use App\Jobs\EvaluateChallengeSubmissionJob;
use App\Models\Challenge;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ChallengeEvidenceTest extends TestCase {
    use RefreshDatabase;
    private function challengeFor(User $user, array $overrides = []): Challenge {
        $path = LearningPath::create(['user_id'=>$user->id,'title'=>'Ruta personalizada','goal'=>'Aprender Excel','status'=>'active']);
        return Challenge::create(array_merge(['user_id'=>$user->id,'learning_path_id'=>$path->id,'milestone'=>25,'generation_status'=>'completed','title'=>'Prueba detallada','description'=>'Descripción','case_context'=>'Caso','objectives'=>['Objetivo uno','Objetivo dos','Objetivo tres'],'deliverables'=>[['name'=>'Archivo','detail'=>'Detalle'],['name'=>'Resumen','detail'=>'Detalle']],'requirements'=>['Requisito uno','Requisito dos','Requisito tres'],'rubric'=>[['name'=>'Criterio','weight'=>100,'description'=>'Detalle']]], $overrides));
    }
    public function test_student_can_submit_detailed_evidence_with_a_private_attachment(): void {
        Queue::fake(); Storage::fake('local');
        $user = User::factory()->create();
        $challenge = $this->challengeFor($user);
        $this->actingAs($user)->post(route('adaptive.challenges.submit', $challenge), ['answer'=>'Hallazgo principal y recomendación.','methodology'=>'Importé el archivo, validé los datos y preparé el resumen.','attachment'=>UploadedFile::fake()->create('reporte.csv', 8, 'text/csv')])->assertRedirect(route('adaptive.challenges.show', $challenge));
        $this->assertDatabaseHas('challenge_submissions', ['challenge_id'=>$challenge->id,'user_id'=>$user->id,'status'=>'queued','attachment_name'=>'reporte.csv']);
        Queue::assertPushed(EvaluateChallengeSubmissionJob::class);
    }
    public function test_student_can_download_challenge_material(): void {
        $user = User::factory()->create();
        $challenge = $this->challengeFor($user, ['title'=>'Prueba con material','resources'=>[['label'=>'Datos','filename'=>'datos.csv','mime'=>'text/csv; charset=UTF-8','content'=>"campo,valor\na,1"]]]);
        $this->actingAs($user)->get(route('adaptive.challenges.resource', [$challenge, 0]))->assertOk()->assertSee('campo,valor');
    }
    public function test_student_cannot_access_a_challenge_belonging_to_another_route_or_user(): void {
        $owner = User::factory()->create(); $visitor = User::factory()->create();
        $challenge = $this->challengeFor($owner);
        $this->actingAs($visitor)->get(route('adaptive.challenges.show', $challenge))->assertNotFound();
    }
}