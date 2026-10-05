@extends('layouts.platform')

@section('content')
<article class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
    <h1 class="text-3xl font-extrabold text-slate-900">Política de cookies</h1>
    <p class="mt-2 text-sm text-slate-500">Última actualización: {{ now()->format('d/m/Y') }}</p>
    <div class="prose prose-slate mt-8 max-w-none text-sm leading-7">
        <p>Aprende Guate utiliza cookies y almacenamiento local para mantener tu sesión, recordar preferencias y hacer que la plataforma sea segura y funcional.</p>
        <h2>Cookies necesarias</h2><p>Permiten autenticación, protección contra solicitudes falsificadas, preferencias de navegación y continuidad de procesos. Sin ellas algunas funciones no pueden operar.</p>
        <h2>Preferencias</h2><p>Podemos guardar la vista de navegación elegida, el estado del recorrido guiado y preferencias de tema o interfaz en el dispositivo.</p>
        <h2>Medición y rendimiento</h2><p>Los registros técnicos pueden utilizarse para detectar errores, mejorar tiempos de respuesta y proteger la plataforma. No usamos cookies para vender información personal.</p>
        <h2>Cómo gestionarlas</h2><p>Puedes borrar o bloquear cookies desde tu navegador. Si bloqueas las necesarias, es posible que debas volver a iniciar sesión o que algunas funciones dejen de estar disponibles.</p>
        <h2>Contacto</h2><p>Para dudas sobre cookies escribe a <a href="mailto:ebatzm@miumg.edu.gt">ebatzm@miumg.edu.gt</a>.</p>
    </div>
</article>
@endsection
