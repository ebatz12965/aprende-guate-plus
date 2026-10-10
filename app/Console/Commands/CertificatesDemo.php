<?php

namespace App\Console\Commands;

use App\Models\LearningPath;
use App\Models\User;
use App\Services\Learning\CertificateService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Rutas ficticias ya completadas, con su certificado, para mostrar la sección en una demostración. */
class CertificatesDemo extends Command
{
    protected $signature = 'certificates:demo {email : Cuenta de demostración} {--remove : Borra las rutas ficticias y sus certificados}';

    protected $description = 'Crea rutas completadas ficticias con certificado para una cuenta de demostración (no usa IA).';

    private const VERSION = 'demo-certificados';

    /** @var list<array{title: string, goal: string, level: string, hours: int, completed: string, phases: array<string, list<string>>}> */
    private const PATHS = [
        ['title' => 'Excel para análisis de datos en la oficina', 'goal' => 'Analizar datos de ventas e inventario con Excel para un puesto administrativo', 'level' => 'Inicial', 'hours' => 32, 'completed' => '2026-08-14 16:20',
            'phases' => [
                'Fundamentos de Excel' => ['Libros, hojas y rangos', 'Fórmulas y referencias', 'Formato de números en quetzales'],
                'Funciones para resumir' => ['SUMA, PROMEDIO y CONTAR', 'SUMAR.SI y CONTAR.SI', 'Funciones lógicas SI'],
                'Tablas y gráficos' => ['Ordenar y filtrar', 'Tablas dinámicas', 'Gráficos que comunican'],
                'Reporte final' => ['Limpieza de datos', 'Reporte mensual de ventas'],
            ]],
        ['title' => 'Atención al cliente y comunicación profesional', 'goal' => 'Atender clientes por teléfono, correo y en persona en una empresa de servicios', 'level' => 'Intermedio', 'hours' => 24, 'completed' => '2026-09-02 11:05',
            'phases' => [
                'Comunicación efectiva' => ['Escucha activa', 'Lenguaje claro y empático'],
                'Canales de atención' => ['Correos profesionales', 'Llamadas y mensajería', 'Atención presencial'],
                'Situaciones difíciles' => ['Manejo de quejas', 'Seguimiento y cierre de casos'],
            ]],
        ['title' => 'Fundamentos de soporte técnico de TI', 'goal' => 'Trabajar como técnico de soporte de TI en una mesa de ayuda', 'level' => 'Inicial', 'hours' => 40, 'completed' => '2026-09-27 18:40',
            'phases' => [
                'Hardware y sistemas operativos' => ['Componentes de una computadora', 'Instalación de Windows', 'Controladores y actualizaciones'],
                'Redes básicas' => ['Direcciones IP y DHCP', 'Wi-Fi y cableado', 'Diagnóstico de conectividad'],
                'Mesa de ayuda' => ['Registro de tickets', 'Prioridades y tiempos de respuesta'],
                'Seguridad para usuarios' => ['Contraseñas y doble factor', 'Correos de phishing'],
                'Caso integrador' => ['Atención de una falla real de oficina'],
            ]],
    ];

    public function handle(CertificateService $certificates): int
    {
        $user = User::where('email', (string) $this->argument('email'))->first();
        if (! $user) {
            $this->error('No existe esa cuenta.');

            return self::FAILURE;
        }

        if ($this->option('remove')) {
            $removed = LearningPath::where('user_id', $user->id)->where('generation_version', self::VERSION)->get()->each(function (LearningPath $path) {
                $path->certificate()->delete();
                $path->delete();
            })->count();
            $this->info("Rutas ficticias borradas: {$removed}");

            return self::SUCCESS;
        }

        foreach (self::PATHS as $data) {
            $path = LearningPath::where('user_id', $user->id)->where('generation_version', self::VERSION)->where('title', $data['title'])->first();
            if (! $path) {
                $completed = Carbon::parse($data['completed'], config('app.timezone'));
                $path = DB::transaction(function () use ($user, $data, $completed) {
                    $path = LearningPath::create(['user_id' => $user->id, 'title' => $data['title'], 'goal' => $data['goal'], 'estimated_level' => $data['level'], 'estimated_hours' => $data['hours'],
                        'generation_version' => self::VERSION, 'status' => 'completed', 'completed_at' => $completed]);
                    $position = 0;
                    foreach ($data['phases'] as $phase => $topics) {
                        $item = $path->items()->create(['title' => $phase, 'area' => 'demo', 'position' => ++$position, 'status' => 'completed',
                            'quiz' => ['question' => 'Fase completada en la demostración', 'options' => ['Sí', 'No', 'Tal vez'], 'answer' => 0]]);
                        foreach ($topics as $index => $topic) {
                            $item->topics()->create(['title' => $topic, 'learning_objective' => 'Aplicar '.$topic.' en el trabajo.', 'practice_type' => 'case_study', 'position' => $index + 1, 'status' => 'completed']);
                        }
                    }
                    $path->forceFill(['created_at' => $completed->copy()->subWeeks(6), 'updated_at' => $completed])->save();

                    return $path;
                });
            }
            $certificate = $certificates->issue($path);
            $this->line("· {$path->title} → certificado {$certificate->code}");
        }
        $this->info('Listo. Míralos en «Mis certificados» con la cuenta '.$user->email.'.');

        return self::SUCCESS;
    }
}
