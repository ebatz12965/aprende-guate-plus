<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_opens_as_a_dialog_over_the_home_page(): void
    {
        $this->get(route('login'))->assertOk()
            // La página inicial se sigue viendo detrás de la ventana.
            ->assertSee('Construye habilidades para tu futuro con IA.')
            ->assertSee('id="login-dialog"', false)->assertSee('Inicia sesión')
            ->assertSee('const autoOpen = true;', false)
            ->assertSee('aprende-guate-app-icon.png', false);
    }

    public function test_the_home_page_has_the_dialog_closed_until_requested(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('id="login-dialog"', false)->assertSee('const autoOpen = false;', false);
    }

    public function test_a_failed_login_reopens_the_dialog_with_the_error(): void
    {
        $user = User::factory()->create();

        $this->from(route('home'))->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta'])->assertRedirect(route('home'));
        $this->get(route('home'))->assertSee('const autoOpen = true;', false)->assertSee(trans('auth.failed'));
    }

    public function test_signed_in_people_do_not_get_the_dialog(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        $this->actingAs($user)->get(route('home'))->assertOk()->assertDontSee('id="login-dialog"', false);
    }
}
