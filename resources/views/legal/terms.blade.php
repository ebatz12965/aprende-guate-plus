@extends('layouts.platform')

@section('content')
<article class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
    <h1 class="text-3xl font-extrabold text-slate-900">Términos y condiciones</h1>
    <p class="mt-2 text-sm text-slate-500">Última actualización: {{ now()->format('d/m/Y') }}</p>
    <div class="prose prose-slate mt-8 max-w-none text-sm leading-7">
        <p>Estos términos regulan el uso de Aprende Guate, plataforma educativa propiedad de Edwing Batz. Al crear una cuenta o utilizar la plataforma aceptas estas condiciones.</p>
        <h2>1. Uso de la plataforma</h2><p>Aprende Guate ofrece diagnósticos, rutas de aprendizaje, contenidos generados o seleccionados, retos, mentorías y herramientas de apoyo. Debes utilizar el servicio de forma lícita, respetuosa y únicamente para fines personales de aprendizaje.</p>
        <h2>2. Cuenta y seguridad</h2><p>Debes proporcionar información veraz, mantener tus credenciales bajo reserva y avisar si detectas un acceso no autorizado. Podemos solicitar verificación de correo o doble factor para proteger la cuenta.</p>
        <h2>3. Contenido y entregas</h2><p>Los contenidos, ejercicios y evaluaciones pueden apoyarse en inteligencia artificial. Debes revisar los resultados y no presentar como propio el trabajo de otras personas. Las evidencias que entregues deben ser tuyas o contar con autorización para usarlas.</p>
        <h2>4. Mentorías y comunicaciones</h2><p>Las mentorías dependen de la disponibilidad y aceptación de las partes. Se espera comunicación respetuosa. Aprende Guate puede enviar avisos necesarios para el servicio según tus preferencias.</p>
        <h2>5. Disponibilidad y cambios</h2><p>Podemos actualizar, suspender o mejorar funciones para mantener la seguridad y calidad del servicio. Procuraremos informar cambios relevantes.</p>
        <h2>6. Contacto</h2><p>Para consultas sobre estos términos escribe a <a href="mailto:ebatzm@miumg.edu.gt">ebatzm@miumg.edu.gt</a>.</p>
    </div>
</article>
@endsection
