@extends('layouts.platform')

@section('content')
<article class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
    <p class="text-xs font-extrabold uppercase tracking-[.16em] text-blue-700">Información legal</p>
    <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Política de privacidad</h1>
    <p class="mt-2 text-sm text-slate-500">Última actualización: {{ now()->format('d/m/Y') }}</p>
    <div class="prose prose-slate mt-8 max-w-none text-sm leading-7">
        <p>Esta política explica cómo Aprende Guate, propiedad de Edwing Batz, recopila y utiliza la información necesaria para prestar el servicio.</p>
        <h2>1. Información que recopilamos</h2><p>Podemos tratar nombre, correo, perfil profesional, preferencias, progreso, entregas, solicitudes de mentoría, mensajes y datos técnicos básicos necesarios para seguridad y funcionamiento.</p>
        <h2>2. Para qué la usamos</h2><p>Usamos la información para autenticarte, personalizar rutas, generar contenidos, evaluar evidencias, coordinar mentorías, enviar notificaciones solicitadas, medir uso y prevenir abusos.</p>
        <h2>3. Inteligencia artificial</h2><p>Algunas funciones procesan datos de la actividad de aprendizaje mediante servicios de inteligencia artificial. Evita incluir información sensible que no sea necesaria para resolver un ejercicio.</p>
        <h2>4. Conservación y seguridad</h2><p>Aplicamos controles técnicos y administrativos razonables. Conservamos la información mientras sea necesaria para la cuenta, obligaciones aplicables o resolución de incidentes, y eliminamos o anonimizamos datos cuando corresponda.</p>
        <h2>5. Tus opciones</h2><p>Puedes actualizar tu perfil, administrar notificaciones, activar la verificación en dos pasos o solicitar orientación sobre tus datos escribiendo a <a href="mailto:ebatzm@miumg.edu.gt">ebatzm@miumg.edu.gt</a>.</p>
        <h2>6. Cambios</h2><p>Publicaremos en esta página cualquier actualización relevante e indicaremos su fecha de vigencia.</p>
    </div>
</article>
@endsection
