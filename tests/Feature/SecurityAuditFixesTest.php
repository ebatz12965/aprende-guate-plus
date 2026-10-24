<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Tests\TestCase;

/** Correcciones de la auditoría de seguridad (corrida 1, puntos 1 a 5). */
class SecurityAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    // 1. Cuentas de ejemplo con contraseña conocida.

    public function test_example_accounts_are_never_seeded_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => UserSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'student@example.com']);
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
    }

    public function test_example_accounts_are_still_seeded_locally(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    // 2. Encabezado Host en los enlaces de los correos.

    public function test_only_the_application_domain_and_configured_hosts_are_trusted(): void
    {
        config(['app.url' => 'https://aprendeguate.com', 'app.trusted_hosts' => ['otro-dominio.gt']]);
        $this->assertTrue(app(Kernel::class)->hasMiddleware(TrustHosts::class));

        Request::setTrustedHosts(array_filter(app(TrustHosts::class)->hosts()));
        try {
            $this->assertSame('aprendeguate.com', Request::create('https://aprendeguate.com/olvide')->getHost());
            $this->assertSame('www.aprendeguate.com', Request::create('https://www.aprendeguate.com/olvide')->getHost());
            $this->assertSame('otro-dominio.gt', Request::create('https://otro-dominio.gt/olvide')->getHost());

            $this->expectException(SuspiciousOperationException::class);
            Request::create('https://atacante.test/olvide')->getHost();
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    // 4. Mini-quiz de cada fase.

    private function pathWithPhases(User $user): LearningPath
    {
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Ruta', 'goal' => 'Meta', 'status' => 'active']);
        foreach ([1, 2] as $position) {
            $item = $path->items()->create(['title' => "Fase {$position}", 'position' => $position, 'status' => 'in_progress',
                'quiz' => ['question' => '¿Cuál?', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
            $item->topics()->create(['title' => "Tema {$position}", 'learning_objective' => 'Aplicar', 'practice_type' => 'case_study', 'position' => 1]);
        }

        return $path;
    }

    public function test_the_quiz_requires_the_previous_phase_and_every_topic(): void
    {
        $user = User::factory()->create();
        $path = $this->pathWithPhases($user);
        [$first, $second] = $path->items()->orderBy('position')->get()->all();

        // La fase 2 sigue bloqueada mientras la 1 no esté completa.
        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $second), ['answer' => 0])->assertForbidden();

        // Sin completar los temas, el quiz no cuenta.
        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $first), ['answer' => 0])->assertSessionHas('quiz_error');
        $this->assertSame('in_progress', $first->fresh()->status);

        $first->topics()->update(['status' => 'completed']);
        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $first), ['answer' => 0])->assertRedirect(route('adaptive.path'));
        $this->assertSame('completed', $first->fresh()->status);
    }

    public function test_a_wrong_answer_makes_the_person_wait_before_trying_again(): void
    {
        $user = User::factory()->create();
        $first = $this->pathWithPhases($user)->items()->where('position', 1)->first();
        $first->topics()->update(['status' => 'completed']);

        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $first), ['answer' => 1])->assertSessionHas('quiz_error');
        // Responder enseguida no se registra ni completa la fase, aunque sea la respuesta correcta.
        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $first), ['answer' => 0])->assertSessionHas('quiz_error');
        $this->assertSame('in_progress', $first->fresh()->status);
        $this->assertSame(1, $first->quizAttempts()->count());

        $this->travel(31)->seconds();
        $this->actingAs($user)->post(route('adaptive.path.item.quiz', $first), ['answer' => 0]);
        $this->assertSame('completed', $first->fresh()->status);
    }

    // 5. Sesiones después de cambiar la contraseña.

    public function test_a_session_opened_with_the_old_password_is_closed(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;
        $user->forceFill(['password' => Hash::make('NuevaClave2026')])->save();

        // Otro equipo conserva en su sesión la huella de la contraseña anterior.
        $this->actingAs($user->fresh())->withSession(['password_hash_web' => Auth::guard('web')->hashPasswordForCookie($oldHash)])
            ->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_changing_the_password_keeps_the_current_session_and_rotates_remember_me(): void
    {
        $user = User::factory()->create(['remember_token' => 'token-anterior']);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NuevaClave2026', $user->fresh()->password));
        $this->assertNotSame('token-anterior', $user->fresh()->remember_token);
        $this->get(route('profile.edit'))->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
