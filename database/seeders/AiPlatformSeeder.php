<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiPlatformSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['title' => 'Análisis de ventas mensual', 'area' => 'Análisis de datos', 'level' => 'Intermedio', 'description' => 'Identifica los productos con mejor desempeño y explica qué decisión tomarías.', 'instructions' => 'Describe los datos que revisarías, dos hallazgos posibles y una recomendación para el equipo.'],
            ['title' => 'Correo profesional a un cliente', 'area' => 'Comunicación', 'level' => 'Inicial', 'description' => 'Redacta una respuesta clara y respetuosa a una solicitud de información.', 'instructions' => 'Incluye saludo, respuesta concreta, siguiente paso y despedida profesional.'],
            ['title' => 'Prioriza un proyecto', 'area' => 'Pensamiento lógico', 'level' => 'Inicial', 'description' => 'Organiza las actividades de un proyecto con tiempo limitado.', 'instructions' => 'Explica qué harías primero, qué delegarías y cómo comunicarías los riesgos.'],
        ] as $challenge) {
            Challenge::firstOrCreate(['title' => $challenge['title']], $challenge);
        }

        User::role('Instructor')->each(function (User $user) {
            MentorProfile::firstOrCreate(['user_id' => $user->id], ['headline' => 'Mentor e instructor', 'specialties' => 'Orientación profesional, aprendizaje práctico']);
        });
    }
}
