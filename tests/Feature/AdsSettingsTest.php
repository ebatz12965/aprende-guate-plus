<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** RF-16: espacios publicitarios preparados para Google AdSense. */
class AdsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT = 'ca-pub-1234567890123456';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);
        $user->assignRole(['Admin', 'Student']);

        return $user;
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        return $user;
    }

    private function enableAds(array $slots = ['inicio' => '1111111111', 'panel' => '2222222222']): void
    {
        PlatformSetting::setString('ads_enabled', '1');
        PlatformSetting::setString('ads_client', self::CLIENT);
        foreach ($slots as $place => $slot) {
            PlatformSetting::setString('ads_slot_'.$place, $slot);
        }
    }

    public function test_ads_are_off_by_default_and_nothing_from_google_is_loaded(): void
    {
        $response = $this->get('/')->assertOk()->assertDontSee('adsbygoogle');
        $this->assertStringNotContainsString('googlesyndication', (string) $response->headers->get('Content-Security-Policy'));
        $this->actingAs($this->student())->get(route('adaptive.dashboard'))->assertOk()->assertDontSee('adsbygoogle');
        $this->get('/ads.txt')->assertNotFound();
        $this->get(route('legal.cookies'))->assertDontSee('Google AdSense');
    }

    public function test_enabled_ads_show_only_in_their_places_and_open_the_csp_only_there(): void
    {
        $this->enableAds();

        $home = $this->get('/')->assertOk()
            ->assertSee('data-ad-client="'.self::CLIENT.'"', false)
            ->assertSee('data-ad-slot="1111111111"', false);
        $this->assertStringContainsString('https://pagead2.googlesyndication.com', (string) $home->headers->get('Content-Security-Policy'));
        // El script de AdSense lleva el nonce de la petición, como todos los demás.
        $this->assertMatchesRegularExpression('#<script async nonce="[A-Za-z0-9]+" src="https://pagead2\.googlesyndication\.com/pagead/js/adsbygoogle\.js\?client='.self::CLIENT.'"#', $home->getContent());

        $this->actingAs($this->student())->get(route('adaptive.dashboard'))->assertOk()->assertSee('data-ad-slot="2222222222"', false);

        // Una página sin espacio publicitario no carga nada ni abre el CSP.
        $about = $this->get(route('about'))->assertOk()->assertDontSee('adsbygoogle');
        $this->assertStringNotContainsString('googlesyndication', (string) $about->headers->get('Content-Security-Policy'));

        $this->get('/ads.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0');
        $this->get(route('legal.cookies'))->assertSee('Google AdSense');
    }

    public function test_a_place_without_a_block_shows_nothing(): void
    {
        $this->enableAds(['inicio' => '', 'panel' => '2222222222']);

        $this->get('/')->assertOk()->assertDontSee('adsbygoogle');
    }

    public function test_the_super_admin_configures_ads_and_it_is_audited(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('admin.ads-settings.edit'))->assertOk()->assertSee('Estado: apagada.');
        $this->actingAs($admin)->put(route('admin.ads-settings.update'), [
            'enabled' => '1', 'client' => ' '.self::CLIENT.' ', 'slot_inicio' => '1111111111', 'slot_panel' => '',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $settings = PlatformSetting::adsSettings();
        $this->assertTrue($settings['enabled']);
        $this->assertSame(self::CLIENT, $settings['client']);
        $this->assertSame(['inicio' => '1111111111', 'panel' => ''], $settings['slots']);
        $this->assertTrue(Activity::where('description', 'Activó los espacios publicitarios')->exists());
    }

    public function test_invalid_ids_are_rejected(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('admin.ads-settings.update'), ['enabled' => '1', 'client' => ''])
            ->assertSessionHasErrors('client');
        $this->actingAs($admin)->put(route('admin.ads-settings.update'), ['enabled' => '1', 'client' => 'pub-123', 'slot_inicio' => '12ab'])
            ->assertSessionHasErrors(['client', 'slot_inicio']);
        $this->assertFalse(PlatformSetting::adsSettings()['enabled']);
    }

    public function test_only_the_super_admin_can_configure_ads(): void
    {
        $role = Role::findOrCreate('Administrador', 'web');
        $role->syncPermissions(['usuarios.ver']);
        $delegated = User::factory()->create();
        $delegated->assignRole(['Student', 'Administrador']);

        $this->actingAs($delegated)->get(route('admin.ads-settings.edit'))->assertForbidden();
        $this->actingAs($this->student())->get(route('admin.ads-settings.edit'))->assertForbidden();
    }
}
