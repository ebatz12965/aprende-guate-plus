<?php

namespace App\Console\Commands;

use App\Models\AiRequest;
use App\Models\AiTokenUsage;
use App\Models\User;
use App\Services\AiPulseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Datos de demostración para el tablero de IA. Solo se ejecuta en una base cuyo nombre
 * contenga "demo", para no mezclar usuarios ni consumo ficticio con los datos reales.
 */
class AiDemoData extends Command
{
    protected $signature = 'ai:demo-data
        {--days=21 : Días de historial que se generan}
        {--live : Después de generar, sigue registrando solicitudes cada pocos segundos (Ctrl+C para detener)}';

    protected $description = 'Genera usuarios y consumo de IA de demostración en una base de datos de demo';

    private const PEOPLE = [
        ['María Ixcot', 'Estudiante'], ['José Tzul', 'Estudiante'], ['Ana López', 'Estudiante'], ['Carlos Méndez', 'Instructor'], ['Lucía Xocop', 'Estudiante'],
        ['Diego Ajú', 'Estudiante'], ['Sofía Estrada', 'Estudiante'], ['Pedro Chamalé', 'Instructor'], ['Valeria Ruiz', 'Estudiante'], ['Juan Pablo Coc', 'Estudiante'],
        ['Andrea Pirir', 'Estudiante'], ['Luis Sican', 'Estudiante'], ['Gabriela Batz', 'Estudiante'], ['Mario Tuyuc', 'Estudiante'],
    ];

    // [proveedor, modelo, peso]
    private const MODELS = [['openai', 'gpt-5.4-mini', 34], ['openai', 'gpt-4o-mini', 22], ['gemini', 'gemini-2.5-flash', 12], ['gemini', 'gemini-2.5-pro', 6], ['anthropic', 'claude-sonnet-5-5', 8], ['anthropic', 'claude-haiku-4-5', 9], ['deepseek', 'deepseek-chat', 9]];

    // [agente, salida máxima, peso]
    private const AGENTS = [['theory_content', 3200, 12], ['exercise_generator', 2400, 11], ['content_reviewer', 3000, 14], ['evaluator', 900, 10], ['tutor', 300, 30], ['diagnostic_designer', 4000, 4], ['goal_analyst', 1800, 4], ['learning_path', 4200, 4], ['content_classifier', 300, 11]];

    private const FAILURES = ['cURL error 28: Operation timed out after 75001 milliseconds', 'Rate limit reached for requests', 'Se alcanzó el presupuesto de tokens configurado.', 'Invalid schema for response_format'];

    public function handle(AiPulseService $pulse): int
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        if (! Str::contains(Str::lower($database), 'demo')) {
            $this->error("La base actual es «{$database}». Este comando solo se ejecuta en una base de demostración (su nombre debe contener «demo»), para no mezclar datos ficticios con los reales.");

            return self::FAILURE;
        }

        $users = $this->users();
        $this->info('Usuarios de demostración: '.count($users).'.');

        $count = 0;
        $start = now()->subDays((int) $this->option('days'));
        $rows = [];
        for ($time = $start->copy(); $time->lt(now()); $time->addSeconds(random_int(60, 900))) {
            $hour = $time->hour;
            // Poca actividad de madrugada, picos a media mañana y por la tarde, menos el fin de semana.
            $chance = ($hour >= 7 && $hour <= 22 ? 1 : .08) * ($time->isWeekend() ? .55 : 1) * (in_array($hour, [10, 11, 15, 16, 19, 20], true) ? 1.8 : 1);
            if (mt_rand() / mt_getrandmax() > $chance * .6) {
                continue;
            }
            $rows[] = $this->request($users, $time->copy());
            if (count($rows) >= 500) {
                $count += $this->store($rows);
                $rows = [];
            }
        }
        $count += $this->store($rows);
        $this->info("Solicitudes de IA generadas: {$count}.");

        if (! $pulse->prices()) {
            // Precios de ejemplo para que el costo se vea en la demostración; se cambian en el orquestador.
            $pulse->savePrices(['gpt-5.4-mini' => ['input' => .25, 'output' => 2], 'gpt-4o-mini' => ['input' => .15, 'output' => .6], 'gemini-2.5-flash' => ['input' => .3, 'output' => 2.5], 'gemini-2.5-pro' => ['input' => 1.25, 'output' => 10], 'claude-sonnet-5-5' => ['input' => 3, 'output' => 15], 'claude-haiku-4-5' => ['input' => 1, 'output' => 5], 'deepseek-chat' => ['input' => .27, 'output' => 1.1]]);
            $this->warn('Se guardaron precios de EJEMPLO por modelo. Cámbialos en el orquestador por los precios reales de cada proveedor.');
        }

        if ($this->option('live')) {
            $this->info('Registrando solicitudes en vivo. Presiona Ctrl+C para detener.');
            while (true) {
                $batch = [];
                for ($i = random_int(1, 3); $i > 0; $i--) {
                    $batch[] = $this->request($users, now());
                }
                $this->store($batch);
                $this->line(now()->format('H:i:s').' · '.count($batch).' solicitudes');
                sleep(random_int(2, 6));
            }
        }

        return self::SUCCESS;
    }

    /** @return array<int, array{id: int, weight: float}> */
    private function users(): array
    {
        $users = [];
        foreach (self::PEOPLE as $index => [$name, $role]) {
            $user = User::firstOrCreate(['email' => Str::slug($name, '.').'@demo.aprendeguate.test'], ['name' => $name, 'password' => bcrypt(Str::random(32)), 'email_verified_at' => now()]);
            $user->syncRoles([Role::findOrCreate($role, 'web')]);
            $users[] = ['id' => $user->id, 'weight' => 26 - $index * 1.7];
        }

        return $users;
    }

    private function request(array $users, Carbon $time): array
    {
        $model = $this->pick(self::MODELS, 2);
        $agent = $this->pick(self::AGENTS, 2);
        $user = $agent[0] === 'content_classifier' && random_int(0, 3) === 0 ? null : $this->pick(array_map(fn ($user) => [$user['id'], $user['weight']], $users), 1)[0];
        $failed = random_int(1, 1000) <= 40;
        $input = random_int(400, 3000);
        $output = $failed ? 0 : (int) round($agent[1] * (random_int(35, 95) / 100));

        return [
            'request_uuid' => (string) Str::uuid(), 'user_id' => $user, 'agent_slug' => $agent[0], 'provider' => $model[0], 'model' => $model[1], 'operation' => $agent[0],
            'input_hash' => hash('sha256', Str::random(16)), 'status' => $failed ? 'failed' : 'completed', 'input_tokens' => $input, 'output_tokens' => $output, 'total_tokens' => $input + $output,
            'duration_ms' => $failed ? 75000 : 1500 + $output * random_int(6, 12), 'failure_reason' => $failed ? self::FAILURES[array_rand(self::FAILURES)] : null,
            'completed_at' => $time, 'created_at' => $time, 'updated_at' => $time,
        ];
    }

    private function store(array $rows): int
    {
        if (! $rows) {
            return 0;
        }
        DB::transaction(function () use ($rows) {
            AiRequest::insert($rows);
            AiTokenUsage::insert(array_map(fn ($row) => [
                'user_id' => $row['user_id'], 'operation' => $row['operation'], 'agent_slug' => $row['agent_slug'], 'provider' => $row['provider'], 'model' => $row['model'],
                'prompt_tokens' => $row['input_tokens'], 'completion_tokens' => $row['output_tokens'], 'total_tokens' => $row['total_tokens'], 'successful' => $row['status'] === 'completed',
                'duration_ms' => $row['duration_ms'], 'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'],
            ], $rows));
        });

        return count($rows);
    }

    /** Elige un elemento según el peso que está en la posición indicada. */
    private function pick(array $items, int $weightIndex): array
    {
        $roll = mt_rand() / mt_getrandmax() * array_sum(array_column($items, $weightIndex));
        foreach ($items as $item) {
            $roll -= $item[$weightIndex];
            if ($roll <= 0) {
                return $item;
            }
        }

        return $items[0];
    }
}
