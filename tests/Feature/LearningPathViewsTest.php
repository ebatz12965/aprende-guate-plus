<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPathViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_path_offers_trail_compact_and_classic_views_with_the_next_step(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Analista de datos junior', 'goal' => 'Trabajar como analista de datos', 'estimated_level' => 'Inicial', 'estimated_hours' => 40, 'status' => 'active']);
        foreach (['Fundamentos de Excel', 'Tablas dinámicas', 'Power BI', 'Proyecto final'] as $index => $title) {
            $item = $path->items()->create(['title' => $title, 'area' => 'Datos', 'description' => "Descripción de {$title}", 'position' => $index + 1, 'status' => $index === 0 ? 'completed' : ($index === 1 ? 'in_progress' : 'pending'),
                'quiz' => ['question' => 'Q', 'options' => ['A', 'B', 'C'], 'answer' => 0]]);
            foreach ([1, 2] as $topic) {
                $item->topics()->create(['title' => "Tema {$topic}", 'learning_objective' => 'Aplicar', 'practice_type' => 'case_study', 'position' => $topic, 'status' => $index === 0 || ($index === 1 && $topic === 1) ? 'completed' : 'pending']);
            }
        }
        Challenge::create(['user_id' => $user->id, 'learning_path_id' => $path->id, 'milestone' => 25, 'title' => 'Reto del 25 %', 'area' => 'Datos', 'level' => 'Inicial', 'description' => 'Reto', 'generation_status' => 'completed']);

        $response = $this->actingAs($user)->get(route('adaptive.path'))->assertOk();

        $response->assertSee('25 %')->assertSee('1 de 4 fases')
            // Siguiente paso: la fase 2, con su avance de temas.
            ->assertSee('Continuar: fase 2')->assertSee('1 de 2 temas')->assertSee('Estás aquí')
            ->assertSee('Recorrido')->assertSee('Compacta')->assertSee('Clásica')
            // El reto del 25 % ya se alcanzó y enlaza al reto; los demás siguen pendientes.
            ->assertSee('Reto del 25 % desbloqueado')->assertSee('Reto opcional del 50 %')
            // La vista clásica sigue disponible con la información original.
            ->assertSee('Repasar módulo')->assertSee('Descripción de Proyecto final');
    }

    public function test_without_a_path_it_invites_to_the_diagnostic(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');

        $this->actingAs($user)->get(route('adaptive.path'))->assertOk()->assertSee('Tu ruta aún no está lista');
    }
}
