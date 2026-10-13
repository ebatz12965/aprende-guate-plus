<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class TwoFactorAndCvSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function userWithTwoFactor(): array
    {
        $twoFactor = app(TwoFactorAuthenticationService::class);
        $secret = $twoFactor->generateSecret();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

        return [$user, $this->currentCode($secret)];
    }

    private function currentCode(string $secret): string
    {
        $method = new ReflectionMethod(TwoFactorAuthenticationService::class, 'codeForCounter');

        return $method->invoke(app(TwoFactorAuthenticationService::class), $secret, intdiv(time(), 30));
    }

    public function test_a_two_factor_code_works_only_once(): void
    {
        [$user, $code] = $this->userWithTwoFactor();

        $this->withSession(['two_factor.login_user_id' => $user->id])->post(route('two-factor.challenge.verify'), ['code' => $code]);
        $this->assertAuthenticatedAs($user);
        auth()->logout();

        // Alguien que vio el código intenta usarlo de nuevo.
        $this->withSession(['two_factor.login_user_id' => $user->id])->post(route('two-factor.challenge.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_the_two_factor_step_locks_after_five_failures_per_account(): void
    {
        [$user, $code] = $this->userWithTwoFactor();

        foreach (range(1, 5) as $i) {
            // Cada intento llega desde una IP distinta: el límite por IP de la ruta no alcanza.
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])->withSession(['two_factor.login_user_id' => $user->id])
                ->post(route('two-factor.challenge.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])->withSession(['two_factor.login_user_id' => $user->id])
            ->post(route('two-factor.challenge.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Demasiados intentos fallidos. Espera 15 minuto(s) e inténtalo de nuevo.']);
        $this->assertGuest();
    }

    public function test_the_challenge_opens_as_a_floating_window_with_the_masked_account(): void
    {
        [$user] = $this->userWithTwoFactor();
        $user->forceFill(['name' => 'Ana López', 'email' => 'analopez@example.com'])->save();

        $this->withSession(['two_factor.login_user_id' => $user->id])->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertSee('id="two-factor-dialog"', false)
            ->assertSee('Confirma que eres tú')
            ->assertSee('Ana López')
            ->assertSee('a'.str_repeat('•', 7).'@example.com')
            ->assertDontSee('analopez@example.com');
    }

    public function test_a_failed_code_tells_how_many_attempts_remain(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->withSession(['two_factor.login_user_id' => $user->id])->post(route('two-factor.challenge.verify'), ['code' => '000000'])
            ->assertSessionHas('two_factor_remaining', 4)
            ->assertSessionHasErrors('code');
    }

    public function test_not_me_discards_the_pending_second_step(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->withSession(['two_factor.login_user_id' => $user->id, 'two_factor.remember' => true])->post(route('two-factor.challenge.cancel'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('two_factor.login_user_id')
            ->assertSessionMissing('two_factor.remember');
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_new_recovery_codes_replace_the_old_ones_and_require_the_password(): void
    {
        [$user] = $this->userWithTwoFactor();
        $old = 'AAAA-1111';
        $user->forceFill(['two_factor_recovery_codes' => [Hash::make($old)]])->save();

        $this->actingAs($user)->post(route('two-factor.recovery-codes.regenerate'), ['current_password' => 'incorrecta'])
            ->assertSessionHasErrorsIn('recoveryCodes', 'current_password');

        $this->actingAs($user)->post(route('two-factor.recovery-codes.regenerate'), ['current_password' => 'password'])
            ->assertRedirect(route('profile.edit', ['section' => 'security']))
            ->assertSessionHas('two_factor.recovery_codes', fn (array $codes) => count($codes) === 8);

        $user->refresh();
        $this->assertCount(8, $user->two_factor_recovery_codes);
        $this->assertFalse(app(TwoFactorAuthenticationService::class)->consumeRecoveryCode($user, $old));
    }

    public function test_the_recovery_codes_pdf_is_only_available_right_after_generating_them(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->actingAs($user)->get(route('two-factor.recovery-codes.download'))->assertNotFound();

        $this->actingAs($user)->post(route('two-factor.recovery-codes.regenerate'), ['current_password' => 'password']);
        $this->actingAs($user)->get(route('profile.edit', ['section' => 'security']))
            ->assertSee('Descargar PDF')->assertSee('Copiar códigos');
        $this->actingAs($user)->get(route('two-factor.recovery-codes.download'))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->travel(16)->minutes();
        $this->actingAs($user)->get(route('two-factor.recovery-codes.download'))->assertNotFound();
    }

    public function test_recovery_codes_cannot_be_generated_without_two_factor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.recovery-codes.regenerate'), ['current_password' => 'password'])->assertForbidden();
    }

    public function test_cvs_are_private_and_only_their_owner_can_download_them(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $owner = User::factory()->create();
        StudentProfile::create(['user_id' => $owner->id]);

        $this->actingAs($owner)->patch(route('adaptive.career.update'), ['bio_visibility' => 'private', 'education_visibility' => 'private', 'work_experience_visibility' => 'private', 'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]);

        $path = StudentProfile::where('user_id', $owner->id)->value('cv_path');
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($owner)->get(route('adaptive.career.cv'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        // Otra persona solo puede pedir su propio CV (no tiene).
        $this->actingAs(User::factory()->create())->get(route('adaptive.career.cv'))->assertNotFound();
        auth()->logout();
        $this->get(route('adaptive.career.cv'))->assertRedirect(route('login'));
    }

    public function test_existing_public_cvs_are_moved_to_the_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('cvs/antiguo.pdf', '%PDF-1.4 prueba');
        StudentProfile::create(['user_id' => $user->id, 'cv_path' => 'cvs/antiguo.pdf']);

        $migration = require database_path('migrations/2026_10_12_101000_move_cvs_to_private_disk.php');
        $migration->up();

        $path = StudentProfile::where('user_id', $user->id)->value('cv_path');
        $this->assertSame("cvs/{$user->id}/antiguo.pdf", $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing('cvs/antiguo.pdf');
    }
}
