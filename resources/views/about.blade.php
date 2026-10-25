@extends('layouts.landing')

@section('title', 'Acerca de nosotros · Aprende Guate')
@section('description', 'Quiénes somos, qué buscamos y cómo trabaja Aprende Guate: aprendizaje con IA y mentoría para conseguir empleo en Guatemala.')

@php
    // Objetivos: los principios del producto (PRODUCT.md). Sin cifras ni testimonios: el proyecto aún no los tiene.
    $objetivos = [
        ['Cada paso acerca a un empleo', 'Medimos cada función por cuánto te ayuda a ser empleable, no por cuánto contenido entrega.'],
        ['Personal, nunca genérico', 'Tu ruta sale de tu diagnóstico, tu meta y tu avance; no de un temario igual para todos.'],
        ['Hacer antes que mirar', 'Practicas con casos de trabajo reales y entregas evidencia que se evalúa. La lectura apoya, no reemplaza.'],
        ['La IA acompaña, las personas importan', 'La IA te guía y evalúa, y siempre tienes mentores humanos y una alternativa cuando la IA no está disponible.'],
    ];
    $pasos = [
        ['Diagnóstico', 'Cuentas qué quieres lograr y respondes un cuestionario que mide tu nivel actual.'],
        ['Ruta personal', 'La plataforma arma tu ruta por fases y temas según tu meta y tus brechas.'],
        ['Lección y práctica', 'Cada tema trae una lección en diapositivas y una práctica con un caso real y retroalimentación.'],
        ['Retos', 'Al 25, 50, 75 y 100 % de tu ruta se desbloquean retos que suman evidencia a tu perfil.'],
        ['Mentoría', 'Conversas con mentores que trabajan en el área que te interesa.'],
        ['Empleabilidad', 'Practicas entrevistas por chat o por voz, armas tu perfil profesional y obtienes tu certificado.'],
    ];
    // Galería: capturas reales de la plataforma con cuentas de demostración. Las fotos de sesiones se agregan en esta misma lista.
    $galeria = [
        ['ruta.webp', 'Ruta personal por fases', 'Tu avance y tu siguiente paso, siempre a la vista.'],
        ['leccion.webp', 'Lecciones en diapositivas', 'Continúas donde te quedaste, en el celular o en la computadora.'],
        ['empleabilidad.webp', 'Simulador de postulación', 'Entrevistas y pruebas como las de un proceso de selección real.'],
        ['certificado.webp', 'Certificado verificable', 'Cada ruta completada se puede comprobar en línea con su código.'],
    ];
@endphp

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-10 pb-16 sm:space-y-14">
    {{-- Portada --}}
    <section class="rounded-3xl bg-slate-950 px-6 py-10 text-white shadow-xl ring-1 ring-transparent sm:px-10 sm:py-14 dark:bg-slate-900 dark:ring-slate-700">
        <h1 class="max-w-3xl text-4xl font-extrabold tracking-tight sm:text-5xl">Aprendizaje con rumbo claro hacia tu primer empleo o tu siguiente paso.</h1>
        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">Aprende Guate es una plataforma para que personas en Guatemala desarrollen habilidades digitales y de empleabilidad con una ruta hecha a su medida, el apoyo de la inteligencia artificial y la guía de mentores.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            @auth
                <a href="{{ route('adaptive.dashboard') }}" class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-sky-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300">Ir a mi panel</a>
            @else
                <a href="{{ route('register') }}" class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-sky-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300">Comenzar mi ruta</a>
            @endauth
            <a href="#como-trabajamos" class="rounded-xl border border-slate-600 px-5 py-3 text-sm font-bold text-white transition hover:border-sky-300 hover:text-sky-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300">Cómo trabajamos</a>
        </div>
    </section>

    {{-- Quiénes somos --}}
    <section class="grid gap-6 lg:grid-cols-[1.1fr_1fr] lg:items-start" aria-labelledby="quienes-somos">
        <div>
            <h2 id="quienes-somos" class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Quiénes somos</h2>
            <div class="mt-4 space-y-4 text-base leading-7 text-slate-700 dark:text-slate-300">
                <p>Aprende Guate nació como proyecto de graduación de Ingeniería en Sistemas en la Universidad Mariano Gálvez de Guatemala, con una pregunta concreta: ¿cómo ayudar a alguien a prepararse para un trabajo sin darle el mismo curso que a todos?</p>
                <p>La respuesta fue una plataforma que primero entiende tu meta y tu nivel, y después te acompaña paso a paso: lecciones, prácticas con casos de trabajo guatemaltecos, retos con evidencia, mentoría y preparación para entrevistas.</p>
                <p>Está pensada para jóvenes que buscan su primer empleo y para personas que ya trabajan y quieren crecer o cambiar de área, empezando por competencias que el mercado pide en todas partes, como Microsoft Office.</p>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">Nuestro objetivo</h3>
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">Que cada persona complete su ruta, tenga evidencia evaluada de lo que sabe hacer y termine con un perfil profesional listo para mostrar a un empleador.</p>
            <dl class="mt-5 grid gap-3 text-sm">
                <div class="rounded-xl bg-blue-50 px-4 py-3 dark:bg-slate-800"><dt class="font-bold text-blue-700 dark:text-sky-300">Para quién</dt><dd class="mt-1 text-slate-700 dark:text-slate-300">Personas en Guatemala que buscan empleo o quieren crecer en el suyo.</dd></div>
                <div class="rounded-xl bg-blue-50 px-4 py-3 dark:bg-slate-800"><dt class="font-bold text-blue-700 dark:text-sky-300">Qué ofrecemos</dt><dd class="mt-1 text-slate-700 dark:text-slate-300">Ruta personal, Tutor IA, retos, mentoría, simulador de entrevistas y certificados.</dd></div>
                <div class="rounded-xl bg-blue-50 px-4 py-3 dark:bg-slate-800"><dt class="font-bold text-blue-700 dark:text-sky-300">Cómo se usa</dt><dd class="mt-1 text-slate-700 dark:text-slate-300">En español, desde el celular o la computadora.</dd></div>
            </dl>
        </div>
    </section>

    {{-- Objetivos y principios --}}
    <section aria-labelledby="principios">
        <h2 id="principios" class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Lo que nos guía</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($objetivos as [$titulo, $texto])
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $titulo }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $texto }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Forma de trabajo --}}
    <section id="como-trabajamos" class="scroll-mt-24" aria-labelledby="titulo-como-trabajamos">
        <h2 id="titulo-como-trabajamos" class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Cómo trabajamos</h2>
        <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">Un recorrido en seis pasos. La IA prepara y evalúa el contenido en segundo plano; tú decides tu ritmo.</p>
        <ol class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($pasos as $i => [$titulo, $texto])
                <li class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-blue-100 text-sm font-extrabold text-blue-700 dark:bg-slate-800 dark:text-sky-300" aria-hidden="true">{{ $i + 1 }}</span>
                    <h3 class="mt-4 text-lg font-extrabold text-slate-900 dark:text-white">{{ $titulo }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $texto }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Galería --}}
    <section aria-labelledby="por-dentro">
        <h2 id="por-dentro" class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">La plataforma por dentro</h2>
        <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">Capturas reales de Aprende Guate, tomadas con cuentas de demostración.</p>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            @foreach ($galeria as [$archivo, $titulo, $texto])
                <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <img src="{{ asset('images/acerca/'.$archivo) }}" alt="Captura de Aprende Guate: {{ Str::lcfirst($titulo) }}" loading="lazy" width="1200" height="720" class="aspect-[5/3] w-full border-b border-slate-200 object-cover object-top dark:border-slate-700">
                    <figcaption class="p-5">
                        <span class="block font-extrabold text-slate-900 dark:text-white">{{ $titulo }}</span>
                        <span class="mt-1 block text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $texto }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- Equipo --}}
    <section class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 md:grid-cols-[auto_1fr] md:items-center dark:border-slate-700 dark:bg-slate-900" aria-labelledby="equipo">
        <span class="grid h-20 w-20 place-items-center rounded-2xl bg-blue-100 text-2xl font-extrabold text-blue-700 dark:bg-slate-800 dark:text-sky-300" aria-hidden="true">EB</span>
        <div>
            <h2 id="equipo" class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Quién está detrás</h2>
            <p class="mt-2 text-base leading-7 text-slate-700 dark:text-slate-300"><strong class="font-bold">Edwing Batz</strong>, fundador y CEO, diseña y desarrolla la plataforma. Los mentores son profesionales que se postulan, responden un cuestionario de su especialidad y son aprobados antes de atender sesiones.</p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">¿Quieres compartir lo que sabes? Crea tu cuenta y solicita ser mentor desde tu panel.</p>
        </div>
    </section>

    {{-- Llamada final --}}
    <section class="rounded-3xl border border-blue-100 bg-blue-50 px-6 py-10 text-center sm:px-10 dark:border-slate-700 dark:bg-slate-900">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Tu ruta empieza con un diagnóstico</h2>
        <p class="mx-auto mt-3 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">Cuéntanos qué quieres lograr y en unos minutos tendrás un plan pensado para ti.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            @auth
                <a href="{{ route('adaptive.dashboard') }}" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">Ir a mi panel</a>
            @else
                <a href="{{ route('register') }}" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">Crear mi cuenta gratis</a>
            @endauth
            <a href="{{ route('legal.faq') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Preguntas frecuentes</a>
        </div>
    </section>
</div>
@endsection
