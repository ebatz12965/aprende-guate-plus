<?php

namespace App\Services;

use Illuminate\Support\Str;

class PlatformGuideService
{
    public function reply(string $question, int $userId): array
    {
        $question = trim($question);
        $normalized = Str::lower(Str::ascii($question));

        if ($this->isOutOfScope($normalized)) {
            return [
                'answer' => 'Soy el guía de Aprende Guate. Solo puedo ayudarte con la navegación, la configuración y el uso de esta plataforma. Para aprender un tema, utiliza el Tutor IA o tu ruta de aprendizaje.',
                'actions' => [],
                'source' => 'guardrail',
            ];
        }

        foreach ($this->faq() as $item) {
            if (collect($item['keywords'])->contains(fn (string $keyword): bool => Str::contains($normalized, $keyword))) {
                return ['answer' => $item['answer'], 'actions' => $item['actions'] ?? [], 'source' => 'faq'];
            }
        }

        return [
            'answer' => 'Puedo orientarte con las opciones precargadas de Aprende Guate. Elige una sugerencia o inicia un recorrido para conocer la plataforma paso a paso.',
            'actions' => [],
            'source' => 'fallback',
        ];
    }

    public function tours(bool $isMentor = false): array
    {
        $tours = [
            [
                'id' => 'general',
                'title' => 'Conoce toda la plataforma',
                'description' => 'Recorre las áreas principales de Aprende Guate y conoce para qué sirve cada una.',
                'steps' => [
                    ['title' => 'Mi panel', 'text' => 'Es tu punto de partida. Aquí ves el siguiente paso de tu ruta, tu avance y accesos rápidos.', 'url' => route('adaptive.dashboard'), 'label' => 'Abrir Mi panel'],
                    ['title' => 'Mi ruta', 'text' => 'Contiene tus fases, temas, lecciones y prácticas organizadas según tu objetivo.', 'url' => route('adaptive.path'), 'label' => 'Abrir Mi ruta'],
                    ['title' => 'Tutor IA', 'text' => 'Úsalo para pedir orientación sobre lo que estás aprendiendo. Quetzi se ocupa únicamente de enseñarte la plataforma.', 'url' => route('adaptive.tutor'), 'label' => 'Abrir Tutor IA'],
                    ['title' => 'Retos', 'text' => 'Son actividades prácticas relacionadas con el avance de tu ruta. Puedes entregar evidencias para recibir evaluación.', 'url' => route('adaptive.challenges'), 'label' => 'Abrir Retos'],
                    ['title' => 'Mentoría profesional', 'text' => 'Busca mentores, solicita orientación y revisa tus sesiones aceptadas y conversaciones.', 'url' => route('adaptive.mentorship'), 'label' => 'Abrir Mentorías'],
                    ['title' => 'Perfil profesional', 'text' => 'Aquí administras tus habilidades, objetivo profesional y datos que personalizan tu experiencia.', 'url' => route('adaptive.career'), 'label' => 'Abrir Perfil'],
                    ['title' => 'Configuración y seguridad', 'text' => 'Desde tu nombre puedes entrar a Configuración, activar el autenticador y cambiar tus preferencias.', 'url' => route('profile.edit', ['section' => 'security']), 'label' => 'Abrir Seguridad'],
                    ['title' => 'Notificaciones', 'text' => 'Consulta avisos, mensajes y recordatorios. También puedes decidir si recibes alertas por correo.', 'url' => route('notifications.index'), 'label' => 'Abrir Notificaciones'],
                ],
            ],
            [
                'id' => 'ruta',
                'title' => 'Aprender con Mi ruta',
                'description' => 'Empieza directamente en el espacio donde avanzas en tus fases y temas.',
                'steps' => [
                    ['title' => 'Revisa tu avance', 'text' => 'El porcentaje de avance te indica qué parte de la ruta ya completaste y qué se desbloquea después.', 'url' => route('adaptive.path'), 'label' => 'Ver avance'],
                    ['title' => 'Abre una fase', 'text' => 'Cada fase agrupa temas concretos. Lee el objetivo antes de entrar a un tema.', 'url' => route('adaptive.path'), 'label' => 'Ver fases'],
                    ['title' => 'Estudia y practica', 'text' => 'En cada tema encontrarás una lección y una práctica cuando el contenido esté listo.', 'url' => route('adaptive.path'), 'label' => 'Continuar ruta'],
                ],
            ],
            [
                'id' => 'apoyo',
                'title' => 'Aprendizaje y apoyo',
                'description' => 'Conoce las herramientas que te acompañan cuando necesitas ayuda.',
                'steps' => [
                    ['title' => 'Tutor IA', 'text' => 'Pregunta sobre tu tema de estudio y recibe pistas para avanzar sin que te resuelva todo el ejercicio.', 'url' => route('adaptive.tutor'), 'label' => 'Ir al Tutor IA'],
                    ['title' => 'Mentoría profesional', 'text' => 'Solicita una conversación con un mentor, revisa respuestas y entra a tus sesiones.', 'url' => route('adaptive.mentorship'), 'label' => 'Ir a Mentorías'],
                    ['title' => 'Sesiones y chats', 'text' => 'Las sesiones aceptadas tienen una conversación privada y pueden aparecer en tu calendario.', 'url' => route('adaptive.mentorship'), 'label' => 'Ver sesiones'],
                ],
            ],
            [
                'id' => 'seguridad',
                'title' => 'Cuenta, seguridad y avisos',
                'description' => 'Configura tu cuenta y mantente al tanto de lo importante.',
                'steps' => [
                    ['title' => 'Seguridad y privacidad', 'text' => 'Activa la verificación en dos pasos con una aplicación autenticadora y administra tus datos.', 'url' => route('profile.edit', ['section' => 'security']), 'label' => 'Abrir seguridad'],
                    ['title' => 'Notificaciones', 'text' => 'Lee mensajes y avisos; las preferencias permiten seleccionar avisos dentro de la aplicación o por correo.', 'url' => route('notifications.index'), 'label' => 'Abrir notificaciones'],
                    ['title' => 'Perfil profesional', 'text' => 'Mantén actualizado tu objetivo y habilidades para que la experiencia sea más útil.', 'url' => route('adaptive.career'), 'label' => 'Abrir perfil'],
                ],
            ],
        ];

        if ($isMentor) {
            $tours[] = [
                'id' => 'mentor',
                'title' => 'Panel del mentor',
                'description' => 'Conoce dónde administrar solicitudes, calendario y conversaciones con estudiantes.',
                'steps' => [
                    ['title' => 'Solicitudes', 'text' => 'Revisa el tema y el perfil de quien solicita una mentoría. Puedes aceptar o rechazar con un mensaje.', 'url' => route('mentor.dashboard'), 'label' => 'Abrir solicitudes'],
                    ['title' => 'Calendario', 'text' => 'Consulta las sesiones programadas por día, semana o mes.', 'url' => route('mentor.calendar'), 'label' => 'Abrir calendario'],
                    ['title' => 'Sesiones y chats', 'text' => 'Entra a las conversaciones activas con tus estudiantes y revisa el historial completo.', 'url' => route('mentor.sessions'), 'label' => 'Abrir sesiones'],
                    ['title' => 'Perfil de mentor', 'text' => 'Configura especialidades, duración de sesiones y el mensaje automático de bienvenida.', 'url' => route('mentor.settings'), 'label' => 'Abrir perfil de mentor'],
                ],
            ];
        }

        return $tours;
    }

    private function isOutOfScope(string $question): bool
    {
        if ($question === '' || Str::length($question) > 500) {
            return true;
        }

        $platformTerms = ['plataforma', 'aprende guate', 'menu', 'configur', 'perfil', 'cuenta', 'seguridad', 'contrasena', 'correo', 'notificacion', 'diagnostico', 'ruta', 'leccion', 'practica', 'reto', 'tutor', 'mentori', 'sesion', 'calendario', 'chat', 'laboratorio', 'iniciar sesion', 'registr', 'cerrar sesion'];
        $sensitiveOrGeneralTerms = ['ignora', 'prompt', 'sistema', 'instrucciones internas', 'clave api', 'token', 'codigo fuente', 'programa', 'programar', 'python', 'javascript', 'php', 'sql', 'receta', 'politica', 'noticia', 'quien es', 'matematic', 'ingles'];

        return ! Str::contains($question, $platformTerms) || Str::contains($question, $sensitiveOrGeneralTerms);
    }

    private function faq(): array
    {
        return [
            ['keywords' => ['mi ruta', 'ruta de aprendizaje', 'fase'], 'answer' => 'En Mi ruta encontrarás las fases, temas y porcentaje de avance. Abre una fase para entrar a sus temas; cuando un tema esté listo podrás ver la lección y la práctica.', 'actions' => [['label' => 'Ir a Mi ruta', 'url' => route('adaptive.path')]]],
            ['keywords' => ['configur', 'seguridad', 'doble factor', 'autenticador'], 'answer' => 'Abre tu nombre en la esquina superior derecha y entra en Configuración. En Seguridad y privacidad puedes activar la verificación en dos pasos, gestionar el autenticador y revisar tus preferencias.', 'actions' => [['label' => 'Abrir configuración', 'url' => route('profile.edit', ['section' => 'security'])]]],
            ['keywords' => ['notificacion', 'aviso'], 'answer' => 'Tus avisos están en Notificaciones. Allí puedes leer mensajes de la plataforma y, desde Preferencias, decidir si recibes avisos dentro de la aplicación o por correo.', 'actions' => [['label' => 'Ver notificaciones', 'url' => route('notifications.index')]]],
            ['keywords' => ['tutor ia', 'tutor'], 'answer' => 'El Tutor IA sirve para pedir orientación sobre lo que estás aprendiendo. Puedes abrirlo desde Aprendizaje y apoyo; para dudas sobre la plataforma, continúa hablando conmigo.', 'actions' => [['label' => 'Abrir Tutor IA', 'url' => route('adaptive.tutor')]]],
            ['keywords' => ['mentori', 'mentor'], 'answer' => 'En Mentoría profesional puedes consultar mentores, enviar una solicitud y revisar el estado. Las sesiones aceptadas aparecen en tus conversaciones y calendario.', 'actions' => [['label' => 'Ver mentorías', 'url' => route('adaptive.mentorship')]]],
            ['keywords' => ['reto', 'laboratorio'], 'answer' => 'Laboratorio y Retos contienen actividades prácticas relacionadas con tu ruta. Los retos se habilitan progresivamente según tu avance y puedes entregar evidencias para evaluación. Los encuentras en el menú Mi ruta.', 'actions' => [['label' => 'Ir a Retos', 'url' => route('adaptive.challenges')]]],
            ['keywords' => ['menu lateral', 'menu superior'], 'answer' => 'Abre tu nombre en la esquina superior derecha y selecciona Usar menú lateral o Usar menú superior. La plataforma recordará tu preferencia en este navegador.', 'actions' => []],
            ['keywords' => ['perfil profesional', 'perfil'], 'answer' => 'Tu Perfil profesional contiene tus habilidades, objetivo y datos que ayudan a personalizar oportunidades y tu ruta. Puedes abrirlo desde el menú Empleabilidad.', 'actions' => [['label' => 'Abrir perfil', 'url' => route('adaptive.career')]]],
            ['keywords' => ['cerrar sesion', 'salir'], 'answer' => 'Abre tu nombre en la esquina superior derecha y selecciona Cerrar sesión. Si usas el menú lateral, encontrarás la misma opción al final.', 'actions' => []],
        ];
    }
}
