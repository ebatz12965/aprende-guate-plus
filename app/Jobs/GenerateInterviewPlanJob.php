<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\InterviewSimulation;
use App\Services\Interviews\InterviewPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Arma el proceso de selección simulado: empresa ficticia, entrevistadores y las cuatro etapas. */
class GenerateInterviewPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $simulationId)
    {
        // Una sola llamada, con el tiempo de las piezas largas (como las lecciones).
        $this->timeout = AiGateway::callTimeout((int) config('ai.content_timeout')) + 20;
    }

    public function handle(InterviewPlanService $plans): void
    {
        $simulation = InterviewSimulation::find($this->simulationId);
        if (! $simulation || $simulation->status !== 'generating') {
            return;
        }

        $plan = $plans->plan($simulation);

        DB::transaction(function () use ($simulation, $plan) {
            $simulation->stages()->delete();
            $stages = [
                ['type' => 'hr_interview', 'title' => 'Entrevista con Recursos Humanos', 'interviewer' => ['name' => $plan['hr']['name'], 'role' => $plan['hr']['role']], 'focus' => $plan['hr']['focus'], 'duration_minutes' => 20,
                    'purpose' => 'Conocer tu motivación, cómo trabajas con otras personas y si el puesto encaja con lo que buscas.'],
                ['type' => 'reasoning_test', 'title' => 'Prueba de razonamiento y juicio situacional', 'interviewer' => null, 'focus' => $plan['reasoning']['focus'], 'duration_minutes' => 20,
                    'purpose' => 'Preguntas de lógica, números y lectura, y situaciones del trabajo en las que eliges qué harías. '.$plan['reasoning']['context'].' Es práctica: no es un test psicométrico validado.'],
                ['type' => 'technical_interview', 'title' => 'Entrevista con '.$plan['technical']['name'].', '.$plan['technical']['role'], 'interviewer' => ['name' => $plan['technical']['name'], 'role' => $plan['technical']['role'], 'style' => $plan['technical']['style']], 'focus' => $plan['technical']['focus'], 'duration_minutes' => 30,
                    'purpose' => 'Comprobar que dominas lo que el puesto exige en el día a día, con preguntas técnicas y casos concretos.'],
                ['type' => 'practical_test', 'title' => 'Prueba práctica: '.$plan['practical']['title'], 'interviewer' => null, 'focus' => [$plan['practical']['deliverable']], 'duration_minutes' => 45,
                    'purpose' => $plan['practical']['description']],
            ];
            foreach ($stages as $index => $stage) {
                $simulation->stages()->create($stage + ['position' => $index + 1, 'status' => $index === 0 ? 'available' : 'locked']);
            }
            $simulation->update([
                'company' => $plan['company'],
                'summary' => $plan['summary'],
                'generated_by' => $plan['source'],
                'status' => 'ready',
                'generation_error' => null,
            ]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        InterviewSimulation::whereKey($this->simulationId)->where('status', 'generating')->update([
            'status' => 'failed',
            'generation_error' => 'No se pudo preparar el proceso. Puedes intentarlo de nuevo.',
        ]);
    }
}
