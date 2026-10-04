<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\MailConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_store_encrypted_smtp_configuration(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));

        $this->actingAs($admin)->put(route('admin.mail-settings.update'), [
            'enabled' => '1', 'host' => 'smtp.example.test', 'port' => 587, 'encryption' => 'tls',
            'username' => 'smtp-user', 'password' => 'application-password',
            'from_address' => 'no-reply@example.com', 'from_name' => 'Aprende Guate',
        ])->assertSessionHas('success');

        $this->assertNotSame('application-password', PlatformSetting::where('key', 'mail_password')->value('value'));
        $this->assertSame('application-password', PlatformSetting::mailSettings()['password']);
        app(MailConfigurationService::class)->apply();
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertTrue(config('mail.mailers.smtp.require_tls'));
        $this->assertSame('no-reply@example.com', config('mail.from.address'));
    }

    public function test_administrator_can_send_a_test_email_with_the_saved_configuration(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin', 'web'));

        foreach ([
            'mail_enabled' => '1',
            'mail_host' => 'smtp.example.test',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => 'smtp-user',
            'mail_from_address' => 'no-reply@example.com',
            'mail_from_name' => 'Aprende Guate',
        ] as $key => $value) {
            PlatformSetting::setString($key, $value);
        }

        $this->actingAs($admin)
            ->post(route('admin.mail-settings.test'), ['test_recipient' => 'destino@example.com'])
            ->assertSessionHas('success');
    }

    public function test_non_administrator_cannot_access_mail_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.mail-settings.edit'))->assertForbidden();
    }
}
