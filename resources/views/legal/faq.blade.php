@extends('layouts.platform')

@section('content')
<article class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
    <h1 class="text-3xl font-extrabold text-slate-900">Preguntas frecuentes</h1>
    <div class="mt-8 space-y-3">
        @foreach ([
            ['¿Cómo se crea mi ruta?', 'Completas el diagnóstico y la plataforma organiza fases, temas, lecciones y prácticas según tu objetivo y nivel.'],
            ['¿Puedo cambiar mi objetivo?', 'Sí. Desde tu perfil profesional puedes actualizarlo y volver a realizar el diagnóstico para obtener una ruta diferente.'],
            ['¿Las prácticas son obligatorias?', 'No. Son actividades opcionales para aplicar lo aprendido y recibir una evaluación cuando entregas evidencia.'],
            ['¿Cómo funciona la mentoría?', 'Puedes solicitar una sesión a un mentor disponible. La sesión queda pendiente hasta que el mentor la acepte y ambos reciben recordatorios.'],
            ['¿Cómo protejo mi cuenta?', 'Usa una contraseña única, confirma tu correo y activa la verificación en dos pasos desde Configuración y seguridad.'],
            ['¿Dónde solicito soporte?', 'Escribe a ebatzm@miumg.edu.gt o llama al 4071 8171.'],
        ] as [$question, $answer])
            <details class="group rounded-xl border border-slate-200 p-4 open:bg-blue-50/50">
                <summary class="cursor-pointer list-none pr-6 text-sm font-extrabold text-slate-800 marker:hidden">{{ $question }} <span class="float-right text-blue-700 transition group-open:rotate-45">＋</span></summary>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $answer }}</p>
            </details>
        @endforeach
    </div>
</article>
@endsection
