@extends('layouts.landing')

@section('description', 'Aprende Guate: diagnóstico, ruta de aprendizaje según tu meta, práctica con casos de trabajo, mentoría y preparación para el empleo en Guatemala.')

@php
    $inicioRuta = auth()->check() ? route('adaptive.dashboard') : route('register');
    $inicioTexto = auth()->check() ? 'Ir a mi panel' : 'Comenzar mi ruta';
    // Capturas reales de la plataforma, tomadas con cuentas de demostración (las mismas de «Acerca de nosotros»).
    $capturas = [
        ['ruta.webp', 'Ruta personal por fases', 'Tu avance y tu siguiente tema, siempre a la vista.', 'Pantalla de una ruta de aprendizaje con sus fases y el avance'],
        ['leccion.webp', 'Lecciones en diapositivas', 'Continúas donde te quedaste, en el celular o en la computadora.', 'Pantalla de una lección en diapositivas'],
        ['empleabilidad.webp', 'Simulador de postulación', 'Entrevistas y pruebas como las de un proceso de selección.', 'Pantalla del simulador de postulación'],
        ['certificado.webp', 'Certificado verificable', 'Cada ruta completada se comprueba en línea con su código.', 'Certificado de una ruta completada'],
    ];
    $areas = [
        ['Ofimática', 'Word, Excel, PowerPoint y Outlook, desde el manejo de archivos hasta los reportes y la automatización.', 'Incluye biblioteca de lecciones'],
        ['Programación', 'Lógica, HTML y CSS, JavaScript y control de versiones para un primer puesto de desarrollo.', 'Ruta según tu nivel'],
        ['Contabilidad', 'Registro de operaciones, conciliaciones, planillas y reportes para un puesto auxiliar.', 'Ruta según tu nivel'],
        ['Idiomas', 'Vocabulario, comprensión y conversación en inglés para el trabajo.', 'Ruta según tu nivel'],
    ];
    $pasos = [
        ['Diagnóstico', 'Escribes tu meta y respondes un cuestionario que mide tu nivel actual.'],
        ['Ruta por fases', 'La plataforma organiza los temas que necesitas, en el orden en que los necesitas.'],
        ['Lección y práctica', 'Cada tema trae una lección y una práctica con un caso de trabajo y retroalimentación.'],
        ['Retos y empleo', 'Retos que suman evidencia a tu perfil, entrevistas de práctica y tu certificado.'],
    ];
    $preguntas = [
        ['¿Necesito conocimientos previos?', 'No. El diagnóstico identifica tu nivel actual y la ruta empieza desde ahí, ya sea que comiences de cero o quieras fortalecer lo que ya sabes.'],
        ['¿Cómo se arma mi ruta?', 'A partir de tu meta y de los resultados del diagnóstico, la plataforma organiza fases, temas, lecciones y prácticas. Puedes ver tu avance y tu siguiente paso desde tu panel.'],
        ['¿Puedo aprender a mi ritmo?', 'Sí. Retomas cada lección donde la dejaste. Las sesiones con mentores se acuerdan según la disponibilidad de ambas personas.'],
        ['¿Puedo ser mentor?', 'Sí. Desde tu cuenta solicitas ser mentor, indicas tu especialidad y completas un cuestionario. La administración revisa y aprueba la solicitud.'],
    ];
@endphp

@section('content')
<style>
    .ini { --ini-ink: #072859; --ini-deep: #1565C0; --ini-blue: #1976D2; --ini-pale: #E3F2FD; --ini-muted: #475569; --ini-line: #dbe3ee; --ini-card: #fff; color: var(--ini-ink); }
    .dark .ini { --ini-ink: #f1f5f9; --ini-deep: #90CAF9; --ini-blue: #64B5F6; --ini-pale: #10294a; --ini-muted: #cbd5e1; --ini-line: #1e3350; --ini-card: #0f172a; }
    .ini h1, .ini h2, .ini h3, .ini p { margin: 0 }
    .ini svg { display: block }
    .ini-icon { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0 }
    .ini-label { font-size: 13px; font-weight: 700; color: var(--ini-deep) }
    .ini-btn { display: inline-flex; align-items: center; gap: 10px; min-height: 48px; padding: 13px 22px; border-radius: 12px; background: #1565C0; color: #fff; font-size: 15px; font-weight: 750; transition: background .2s }
    .ini-btn:hover { background: #0D47A1 }
    .ini-btn:focus-visible, .ini-link:focus-visible, .ini summary:focus-visible, .ini-round:focus-visible, .ini-dot:focus-visible { outline: 3px solid #42A5F5; outline-offset: 3px }
    .ini-link { display: inline-flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: var(--ini-ink) }
    .ini-link:hover { color: var(--ini-deep) }

    /* Portada */
    .ini-hero { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: clamp(28px, 4vw, 64px); align-items: center; padding: clamp(8px, 2vw, 24px) 0 8px }
    .ini-hero h1 { margin-top: 18px; font-size: clamp(40px, 4.6vw, 68px); line-height: 1.04; letter-spacing: -.05em; font-weight: 850 }
    .ini-hero h1 span { color: var(--ini-deep) }
    .ini-lead { margin-top: 22px !important; max-width: 470px; font-size: 17px; line-height: 1.75; color: var(--ini-muted) }
    .ini-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 22px; margin-top: 30px }
    .ini-note { display: flex; align-items: center; gap: 8px; margin-top: 22px !important; font-size: 13px; color: var(--ini-muted) }
    .ini-note .ini-icon { width: 16px; height: 16px; color: var(--ini-deep) }

    /* Carrusel de capturas */
    .ini-shots { border: 1px solid var(--ini-line); border-radius: 20px; background: var(--ini-card); box-shadow: 0 18px 40px rgba(7, 40, 89, .10); overflow: hidden }
    .ini-frame { position: relative; aspect-ratio: 5 / 3; background: #eef4fb }
    .dark .ini-frame { background: #0b1324 }
    .ini-slide { position: absolute; inset: 0; margin: 0; opacity: 0; visibility: hidden; transition: opacity .6s }
    .ini-slide.is-active { opacity: 1; visibility: visible }
    .ini-slide img { width: 100%; height: 100%; object-fit: cover; object-position: top }
    .ini-shots-foot { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; border-top: 1px solid var(--ini-line) }
    .ini-caption strong { display: block; font-size: 15px }
    .ini-caption span { display: block; margin-top: 3px; font-size: 13px; color: var(--ini-muted) }
    .ini-controls { display: flex; align-items: center; gap: 6px; flex-shrink: 0 }
    .ini-dots { display: flex; gap: 4px; margin-right: 6px }
    .ini-dot { width: 22px; height: 28px; padding: 0; border: 0; background: transparent; cursor: pointer }
    .ini-dot:before { content: ""; display: block; height: 3px; border-radius: 3px; background: var(--ini-line) }
    .ini-dot[aria-current="true"]:before { background: var(--ini-deep) }
    .ini-round { width: 36px; height: 36px; display: grid; place-items: center; border: 1px solid var(--ini-line); border-radius: 50%; background: transparent; color: var(--ini-ink); cursor: pointer }
    .ini-round:hover { background: var(--ini-pale) }
    .ini-round .ini-icon { width: 16px; height: 16px }

    /* Franja de beneficios */
    .ini-benefits { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px; margin-top: 40px; padding: 24px 0; border-top: 1px solid var(--ini-line); border-bottom: 1px solid var(--ini-line) }
    .ini-benefit { display: flex; gap: 12px; align-items: flex-start }
    .ini-benefit .ini-icon { color: var(--ini-deep); margin-top: 2px }
    .ini-benefit b { display: block; font-size: 15px }
    .ini-benefit small { display: block; margin-top: 3px; font-size: 13px; line-height: 1.5; color: var(--ini-muted) }

    /* Secciones */
    .ini-section { padding: clamp(48px, 6vw, 80px) 0 0 }
    .ini-head { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 28px }
    .ini-head h2, .ini-steps-copy h2, .ini-faq h2, .ini-cta h2 { margin-top: 10px; font-size: clamp(28px, 3vw, 40px); line-height: 1.15; letter-spacing: -.035em; font-weight: 800 }
    .ini-head > p { max-width: 380px; font-size: 15px; line-height: 1.7; color: var(--ini-muted) }
    .ini-areas { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px }
    .ini-area { display: flex; flex-direction: column; padding: 24px; border: 1px solid var(--ini-line); border-radius: 16px; background: var(--ini-card) }
    .ini-area h3 { font-size: 20px; letter-spacing: -.02em }
    .ini-area p { margin-top: 10px !important; font-size: 14px; line-height: 1.65; color: var(--ini-muted); flex: 1 }
    .ini-area small { margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--ini-line); font-size: 12px; font-weight: 700; color: var(--ini-deep) }
    .ini-areas-note { margin-top: 18px !important; font-size: 14px; color: var(--ini-muted) }

    /* Cómo funciona */
    .ini-steps { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: clamp(28px, 5vw, 80px); align-items: center; margin-top: clamp(48px, 6vw, 80px); padding: clamp(32px, 5vw, 64px); border-radius: 24px; background: #072859; color: #fff }
    .dark .ini-steps { background: #0b1d3a; box-shadow: inset 0 0 0 1px #1e3350 }
    .ini-steps .ini-label { color: #90CAF9 }
    .ini-steps-copy > p { margin-top: 16px !important; max-width: 420px; font-size: 15px; line-height: 1.75; color: #c7d7ea }
    .ini-step-list { display: grid; gap: 22px; margin: 0; padding: 0; list-style: none; counter-reset: paso }
    .ini-step-list li { display: flex; gap: 16px; counter-increment: paso }
    .ini-step-list li:before { content: counter(paso, decimal-leading-zero); display: grid; place-items: center; width: 36px; height: 36px; flex-shrink: 0; border: 1px solid rgba(144, 202, 249, .45); border-radius: 50%; font-size: 12px; font-weight: 700; color: #90CAF9 }
    .ini-step-list h3 { font-size: 17px }
    .ini-step-list p { margin-top: 5px !important; font-size: 14px; line-height: 1.6; color: #c7d7ea }

    /* Acompañamiento */
    .ini-support { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px }
    .ini-card { display: flex; flex-direction: column; padding: 28px; border: 1px solid var(--ini-line); border-radius: 16px; background: var(--ini-card) }
    .ini-card-icon { display: grid; place-items: center; width: 44px; height: 44px; margin-bottom: 20px; border-radius: 12px; background: var(--ini-pale); color: var(--ini-deep) }
    .ini-card h3 { font-size: 21px; letter-spacing: -.02em }
    .ini-card p { margin-top: 10px !important; font-size: 14px; line-height: 1.7; color: var(--ini-muted) }
    .ini-quote { margin-top: 18px !important; padding: 14px 16px; border-left: 3px solid var(--ini-blue); border-radius: 8px; background: var(--ini-pale); font-size: 14px; line-height: 1.6; color: var(--ini-ink) !important }

    /* Preguntas */
    .ini-faq { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); gap: clamp(28px, 5vw, 72px) }
    .ini-faq > div > p { margin-top: 14px !important; font-size: 15px; color: var(--ini-muted) }
    .ini-faq > div > p a { color: var(--ini-deep); font-weight: 700 }
    .ini details { padding: 20px 0; border-bottom: 1px solid var(--ini-line) }
    .ini details:first-child { border-top: 1px solid var(--ini-line) }
    .ini summary { display: flex; justify-content: space-between; gap: 20px; font-size: 16px; font-weight: 700; cursor: pointer; list-style: none }
    .ini summary::-webkit-details-marker { display: none }
    .ini summary:after { content: "+"; color: var(--ini-deep); font-size: 22px; line-height: 1; font-weight: 400 }
    .ini details[open] summary:after { content: "−" }
    .ini details p { margin-top: 12px !important; max-width: 580px; font-size: 15px; line-height: 1.75; color: var(--ini-muted) }

    /* Llamado final */
    .ini-cta { display: flex; align-items: center; justify-content: space-between; gap: 28px; margin-top: clamp(48px, 6vw, 80px); padding: clamp(28px, 4vw, 44px); border-radius: 20px; background: var(--ini-pale) }
    .ini-cta p { margin-top: 10px !important; font-size: 15px; color: var(--ini-muted) }

    @media (max-width: 1100px) {
        .ini-areas { grid-template-columns: repeat(2, minmax(0, 1fr)) }
        .ini-support { grid-template-columns: 1fr }
    }
    @media (max-width: 900px) {
        .ini-hero, .ini-steps, .ini-faq { grid-template-columns: 1fr }
        .ini-benefits { grid-template-columns: repeat(2, minmax(0, 1fr)) }
        .ini-head { display: block }
        .ini-head > p { margin-top: 12px !important; max-width: none }
    }
    @media (max-width: 560px) {
        .ini-hero h1 { font-size: clamp(36px, 11vw, 46px) }
        .ini-lead { font-size: 16px }
        .ini-areas, .ini-benefits { grid-template-columns: 1fr }
        .ini-shots-foot { flex-direction: column; align-items: flex-start }
        .ini-cta { display: block }
        .ini-cta .ini-btn { margin-top: 20px }
    }
    @media (prefers-reduced-motion: reduce) { .ini-slide { transition: none } }
</style>

<svg width="0" height="0" aria-hidden="true" style="position:absolute">
    <defs>
        <symbol id="ini-ruta" viewBox="0 0 24 24"><circle cx="6" cy="5" r="2"/><circle cx="18" cy="19" r="2"/><path d="M8 5h7a4 4 0 0 1 0 8H9a3 3 0 0 0 0 6h7"/></symbol>
        <symbol id="ini-chat" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 10.5h7M8.5 14h4.5"/></symbol>
        <symbol id="ini-practica" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 12l2 2 4-4M9 17h6"/></symbol>
        <symbol id="ini-personas" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M16 5a3 3 0 0 1 0 6M21 20v-1a6 6 0 0 0-3-5.2"/></symbol>
        <symbol id="ini-maletin" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/></symbol>
        <symbol id="ini-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
        <symbol id="ini-flecha" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
        <symbol id="ini-izq" viewBox="0 0 24 24"><path d="m15 6-6 6 6 6"/></symbol>
        <symbol id="ini-der" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></symbol>
        <symbol id="ini-pausa" viewBox="0 0 24 24"><path d="M9 6v12M15 6v12"/></symbol>
        <symbol id="ini-play" viewBox="0 0 24 24"><path d="M8 5.5v13l10-6.5-10-6.5Z"/></symbol>
    </defs>
</svg>

<div class="ini">
    <section class="ini-hero">
        <div>
            <p class="ini-label">Aprendizaje y empleabilidad para Guatemala</p>
            <h1>Aprende a tu ritmo. Avanza hacia <span>lo que sueñas.</span></h1>
            <p class="ini-lead">Un diagnóstico mide tu nivel, tu ruta se arma según tu meta y practicas con casos de trabajo. Cuando lo necesites, el Tutor y los mentores te ayudan a seguir.</p>
            <div class="ini-actions">
                <a href="{{ $inicioRuta }}" class="ini-btn">{{ $inicioTexto }} <svg class="ini-icon" aria-hidden="true"><use href="#ini-flecha"/></svg></a>
                <a href="#como-funciona" class="ini-link">Así funciona</a>
            </div>
            <p class="ini-note"><svg class="ini-icon" aria-hidden="true"><use href="#ini-check"/></svg> Desde tu nivel actual y con una meta que eliges tú.</p>
        </div>

        <div class="ini-shots" role="region" aria-roledescription="carrusel" aria-label="Capturas de la plataforma" data-carrusel>
            <div class="ini-frame">
                @foreach ($capturas as $i => [$archivo, $titulo, $texto, $alt])
                    <figure class="ini-slide {{ $i === 0 ? 'is-active' : '' }}" role="group" aria-roledescription="diapositiva" aria-label="{{ $i + 1 }} de {{ count($capturas) }}" @if ($i) aria-hidden="true" @endif data-titulo="{{ $titulo }}" data-texto="{{ $texto }}">
                        <img src="{{ asset('images/acerca/'.$archivo) }}" alt="{{ $alt }}" width="1200" height="720" @if ($i) loading="lazy" @else fetchpriority="high" @endif>
                    </figure>
                @endforeach
            </div>
            <div class="ini-shots-foot">
                <p class="ini-caption" aria-live="polite"><strong data-titulo>{{ $capturas[0][1] }}</strong><span data-texto>{{ $capturas[0][2] }}</span></p>
                <div class="ini-controls">
                    <div class="ini-dots">
                        @foreach ($capturas as $i => $captura)
                            <button type="button" class="ini-dot" aria-label="Mostrar captura {{ $i + 1 }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}"></button>
                        @endforeach
                    </div>
                    <button type="button" class="ini-round" data-pausa aria-label="Pausar carrusel"><svg class="ini-icon" aria-hidden="true"><use href="#ini-pausa"/></svg></button>
                    <button type="button" class="ini-round" data-anterior aria-label="Captura anterior"><svg class="ini-icon" aria-hidden="true"><use href="#ini-izq"/></svg></button>
                    <button type="button" class="ini-round" data-siguiente aria-label="Captura siguiente"><svg class="ini-icon" aria-hidden="true"><use href="#ini-der"/></svg></button>
                </div>
            </div>
        </div>
    </section>

    <section class="ini-benefits" aria-label="Lo que incluye la plataforma">
        <div class="ini-benefit"><svg class="ini-icon" aria-hidden="true"><use href="#ini-ruta"/></svg><div><b>Tu propia ruta</b><small>Un plan según tu meta y tu diagnóstico</small></div></div>
        <div class="ini-benefit"><svg class="ini-icon" aria-hidden="true"><use href="#ini-chat"/></svg><div><b>Tutor para tus dudas</b><small>Pide una pista o una explicación en cada tema</small></div></div>
        <div class="ini-benefit"><svg class="ini-icon" aria-hidden="true"><use href="#ini-practica"/></svg><div><b>Práctica con casos</b><small>Del concepto a una tarea de trabajo</small></div></div>
        <div class="ini-benefit"><svg class="ini-icon" aria-hidden="true"><use href="#ini-personas"/></svg><div><b>Mentoría</b><small>Personas que trabajan en el área</small></div></div>
    </section>

    <section id="que-aprender" class="ini-section">
        <div class="ini-head">
            <div><p class="ini-label">Qué puedes aprender</p><h2>¿Qué quieres lograr?</h2></div>
            <p>Escribes tu meta con tus palabras y el diagnóstico le da forma a la ruta. Estas son algunas de las áreas más comunes.</p>
        </div>
        <div class="ini-areas">
            @foreach ($areas as [$titulo, $texto, $pie])
                <article class="ini-area"><h3>{{ $titulo }}</h3><p>{{ $texto }}</p><small>{{ $pie }}</small></article>
            @endforeach
        </div>
        <p class="ini-areas-note">¿Tienes otra meta? También puedes escribirla al crear tu ruta.</p>
    </section>

    <x-espacio-publicitario lugar="inicio" class="mt-12" />

    <section id="como-funciona" class="ini-steps">
        <div class="ini-steps-copy">
            <p class="ini-label">Cómo funciona</p>
            <h2>No todos empezamos en el mismo lugar.</h2>
            <p>Por eso tu aprendizaje empieza contigo: lo que ya sabes, lo que te falta y hasta dónde quieres llegar.</p>
        </div>
        <ol class="ini-step-list">
            @foreach ($pasos as [$titulo, $texto])
                <li><div><h3>{{ $titulo }}</h3><p>{{ $texto }}</p></div></li>
            @endforeach
        </ol>
    </section>

    <section id="acompanamiento" class="ini-section">
        <div class="ini-head">
            <div><p class="ini-label">Acompañamiento</p><h2>Aprendes por tu cuenta, pero no estás solo.</h2></div>
            <p>Apoyo en cada tema, personas con experiencia y práctica para el momento de postular.</p>
        </div>
        <div class="ini-support">
            <article class="ini-card">
                <span class="ini-card-icon"><svg class="ini-icon" aria-hidden="true"><use href="#ini-chat"/></svg></span>
                <h3>Tutor en cada tema</h3>
                <p>Retoma un concepto, pide una pista o encuentra otra forma de entenderlo, sin salir de la lección.</p>
                <p class="ini-quote">«Antes de la fórmula, ¿qué dato quieres obtener de la tabla?»</p>
            </article>
            <article class="ini-card">
                <span class="ini-card-icon"><svg class="ini-icon" aria-hidden="true"><use href="#ini-personas"/></svg></span>
                <h3>Mentores</h3>
                <p>Solicita orientación a un mentor del área que te interesa, conversa sobre tus objetivos y acuerden una sesión.</p>
            </article>
            <article class="ini-card">
                <span class="ini-card-icon"><svg class="ini-icon" aria-hidden="true"><use href="#ini-maletin"/></svg></span>
                <h3>Preparación para el empleo</h3>
                <p>Practica entrevistas por chat o por voz, arma tu perfil profesional y obtén un certificado verificable al completar tu ruta.</p>
            </article>
        </div>
    </section>

    <section id="preguntas" class="ini-section">
        <div class="ini-faq">
            <div>
                <p class="ini-label">Preguntas frecuentes</p>
                <h2>Antes de empezar</h2>
                <p>¿Tienes otra duda? Revisa todas las <a href="{{ route('legal.faq') }}">preguntas frecuentes</a>.</p>
            </div>
            <div>
                @foreach ($preguntas as [$pregunta, $respuesta])
                    <details><summary>{{ $pregunta }}</summary><p>{{ $respuesta }}</p></details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ini-cta">
        <div>
            <h2>Empieza con una meta.</h2>
            <p>Haz tu diagnóstico y tu ruta queda lista para comenzar.</p>
        </div>
        <a href="{{ $inicioRuta }}" class="ini-btn">{{ $inicioTexto }} <svg class="ini-icon" aria-hidden="true"><use href="#ini-flecha"/></svg></a>
    </section>
</div>

<script nonce="{{ Vite::cspNonce() }}">
    (() => {
        const root = document.querySelector('[data-carrusel]');
        if (!root) return;
        const slides = [...root.querySelectorAll('.ini-slide')];
        const dots = [...root.querySelectorAll('.ini-dot')];
        const pausa = root.querySelector('[data-pausa]');
        const titulo = root.querySelector('[data-titulo]');
        const texto = root.querySelector('[data-texto]');
        const reducido = matchMedia('(prefers-reduced-motion: reduce)');
        let actual = 0, pausado = reducido.matches, timer;

        const mostrar = (i) => {
            actual = (i + slides.length) % slides.length;
            slides.forEach((s, n) => { s.classList.toggle('is-active', n === actual); s.setAttribute('aria-hidden', String(n !== actual)); });
            dots.forEach((d, n) => d.setAttribute('aria-current', String(n === actual)));
            titulo.textContent = slides[actual].dataset.titulo;
            texto.textContent = slides[actual].dataset.texto;
        };
        const detener = () => clearInterval(timer);
        const iniciar = () => { detener(); if (!pausado && !document.hidden && !root.matches(':hover') && !root.contains(document.activeElement)) timer = setInterval(() => mostrar(actual + 1), 6000); };
        const etiqueta = () => {
            pausa.setAttribute('aria-label', pausado ? 'Reanudar carrusel' : 'Pausar carrusel');
            pausa.querySelector('use').setAttribute('href', pausado ? '#ini-play' : '#ini-pausa');
        };
        const manual = (i) => { mostrar(i); pausado = true; etiqueta(); detener(); };

        dots.forEach((d, n) => d.addEventListener('click', () => manual(n)));
        root.querySelector('[data-anterior]').addEventListener('click', () => manual(actual - 1));
        root.querySelector('[data-siguiente]').addEventListener('click', () => manual(actual + 1));
        pausa.addEventListener('click', () => { pausado = !pausado; etiqueta(); iniciar(); });
        root.addEventListener('mouseenter', detener);
        root.addEventListener('mouseleave', iniciar);
        root.addEventListener('focusin', detener);
        root.addEventListener('focusout', () => setTimeout(iniciar, 0));
        document.addEventListener('visibilitychange', iniciar);
        let x0 = 0;
        root.addEventListener('touchstart', (e) => { x0 = e.changedTouches[0].clientX; }, { passive: true });
        root.addEventListener('touchend', (e) => { const d = e.changedTouches[0].clientX - x0; if (Math.abs(d) > 50) manual(actual + (d < 0 ? 1 : -1)); }, { passive: true });
        etiqueta();
        iniciar();
    })();
</script>
@endsection
