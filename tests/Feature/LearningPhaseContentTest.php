<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPhaseContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_formula_phase_has_one_simulation_and_five_exercises_for_each_topic(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Excel', 'goal' => 'Aprender Excel', 'status' => 'active']);
        $item = $path->items()->create([
            'title' => 'Fórmulas básicas', 'area' => 'Fórmulas', 'description' => 'Aprende a sumar, restar, multiplicar y dividir.',
            'content' => ['concept' => 'Las fórmulas realizan cálculos en Excel.', 'example' => '=A1+B1 suma valores.', 'activity' => 'Practica con una hoja.'],
            'quiz' => ['question' => '¿Cuál fórmula suma?', 'options' => ['=A1+B1', '=A1-B1', '=A1/B1'], 'answer' => 0],
        ]);

        $this->actingAs($user)->get(route('adaptive.path.item.show', $item))->assertOk()
            ->assertSee('BUSCARV')->assertSee('SUMA por rango')->assertSee('Ver 6 simulaciones')
            ->assertSee('=SUMA(A1:B1)')->assertSee('30 ejercicios')->assertSee('12 + 8');
    }

    public function test_components_phase_has_a_simulation_and_five_activities_for_each_topic(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['user_id' => $user->id, 'title' => 'Computación', 'goal' => 'Conocer computación', 'status' => 'active']);
        $item = $path->items()->create([
            'title' => 'Componentes básicos', 'area' => 'Computación', 'description' => 'Conoce las partes de una computadora.',
            'content' => ['concept' => 'Identifica los componentes básicos.', 'example' => 'Observa cada componente.', 'activity' => 'Relaciona componente y función.'],
            'quiz' => ['question' => '¿Qué componente procesa?', 'options' => ['Procesador', 'Monitor', 'Teclado'], 'answer' => 0],
        ]);

        $this->actingAs($user)->get(route('adaptive.path.item.show', $item))->assertOk()
            ->assertSee('Procesador')->assertSee('Memoria RAM')->assertSee('Ver 5 simulaciones')
            ->assertSee('25 ejercicios')->assertSee('Actividad 1: aplica Procesador');
    }
}
