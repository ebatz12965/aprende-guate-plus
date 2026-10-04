<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_update_ai_and_diagnostic_limits(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));

        $this->actingAs($admin)->put(route('admin.ai-settings.update'), [
            'tutor_minute_limit' => 4,
            'tutor_daily_limit' => 25,
            'generation_minute_limit' => 8,
            'generation_daily_limit' => 90,
            'diagnostic_min_characters' => 20,
            'diagnostic_max_characters' => 300,
            'ai_token_budget' => 15000,
        ])->assertSessionHas('success');

        $this->assertSame(4, PlatformSetting::aiLimits()['tutor_minute_limit']);
        $this->assertSame(25, PlatformSetting::aiLimits()['tutor_daily_limit']);
        $this->assertSame(8, PlatformSetting::aiLimits()['generation_minute_limit']);
        $this->assertSame(90, PlatformSetting::aiLimits()['generation_daily_limit']);
        $this->assertSame(20, PlatformSetting::aiLimits()['diagnostic_min_characters']);
        $this->assertSame(300, PlatformSetting::aiLimits()['diagnostic_max_characters']);
        $this->assertSame(15000, PlatformSetting::aiLimits()['ai_token_budget']);
    }

    public function test_diagnostic_form_uses_the_limits_saved_by_an_administrator(): void
    {
        PlatformSetting::setInteger('diagnostic_min_characters', 25);
        PlatformSetting::setInteger('diagnostic_max_characters', 280);
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('adaptive.diagnostic'))
            ->assertOk()
            ->assertSee('minlength="25"', false)
            ->assertSee('maxlength="280"', false);
    }
}
