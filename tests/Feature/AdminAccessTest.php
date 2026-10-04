<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_unverified_email_is_sent_to_verification(): void
    {
        $admin = User::factory()->unverified()->create();
        $admin->assignRole(Role::findOrCreate('Admin'));

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_user_list_is_paginated(): void
    {
        $admin = User::factory()->create(['created_at' => now()->subYear()]);
        $admin->assignRole(Role::findOrCreate('Admin'));
        User::factory()->count(30)->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('?page=2', false);
        $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]))->assertOk()->assertSee($admin->email);
    }
}
