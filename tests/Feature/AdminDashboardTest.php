<?php
namespace Tests\Feature;
use App\Models\AiTokenUsage;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
class AdminDashboardTest extends TestCase {
    use RefreshDatabase;
    public function test_dashboard_shows_dynamic_user_and_token_summaries(): void {
        $admin = User::factory()->create(['name'=>'Administrador']); $admin->assignRole(Role::findOrCreate('Admin', 'web'));
        $users = User::factory()->count(6)->create();
        PlatformSetting::setInteger('ai_token_budget', 1000);
        AiTokenUsage::create(['user_id'=>$users->first()->id,'operation'=>'tutor_reply','model'=>'test','prompt_tokens'=>100,'completion_tokens'=>150,'total_tokens'=>250,'successful'=>true]);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Usuarios registrados')->assertSee('Tokens disponibles')->assertSee('750')->assertSee('Usuarios con mayor consumo IA')->assertSee($users->first()->name);
    }
}