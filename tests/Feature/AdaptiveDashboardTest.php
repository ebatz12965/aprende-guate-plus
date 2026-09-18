<?php
namespace Tests\Feature;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdaptiveDashboardTest extends TestCase {
    use RefreshDatabase;
    public function test_dashboard_prioritizes_the_phase_in_progress(): void {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id'=>$user->id,'title'=>'Ruta','goal'=>'Meta','status'=>'active']);
        $path->items()->create(['title'=>'Fundamentos esenciales','area'=>'Base','description'=>'Primera fase','position'=>1,'status'=>'in_progress','quiz'=>['question'=>'Q','options'=>['A','B','C'],'answer'=>0]]);
        $path->items()->create(['title'=>'Segunda fase','area'=>'Base','description'=>'Segunda','position'=>2,'status'=>'pending','quiz'=>['question'=>'Q','options'=>['A','B','C'],'answer'=>0]]);
        $this->actingAs($user)->get(route('adaptive.dashboard'))->assertOk()->assertSee('Fundamentos esenciales')->assertDontSee('Segunda fase');
    }
}