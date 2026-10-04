@extends('layouts.landing')

@section('content')
<section class="hero">
    <div class="hero-content">
        <p class="eyebrow"><span class="dot"></span> Aprendizaje que se adapta a ti</p>
        <h1>Construye habilidades para tu futuro con IA.</h1>
        <p>Diagnóstico, ruta personalizada, Tutor IA, retos prácticos y mentoría profesional en una sola plataforma para Guatemala.</p>
        <div class="hero-actions">
            @auth
                <a href="{{ route('adaptive.dashboard') }}" class="button button-light">Ir a mi panel</a>
            @else
                <a href="{{ route('register') }}" class="button button-light">Comenzar mi ruta</a>
                <a href="{{ route('login') }}" class="button button-outline">Iniciar sesión</a>
            @endauth
        </div>
    </div>
</section>
<div class="scroll-hint" aria-hidden="true">
    <span class="scroll-hint-dot"></span>
    <span class="scroll-hint-dot"></span>
    <span class="scroll-hint-dot"></span>
</div>
<section class="features">
    @foreach ([['01', 'Diagnóstico inteligente', 'Conoce tu nivel actual y las habilidades que puedes fortalecer.'], ['02', 'Ruta personalizada', 'Recibe pasos concretos según tu objetivo profesional.'], ['03', 'Tutor IA', 'Pregunta, practica y recibe guía en cualquier momento.'], ['04', 'Retos prácticos', 'Aplica lo aprendido en situaciones cercanas al trabajo real.'], ['05', 'Mentoría profesional', 'Conecta con personas que te ayudan a avanzar.'], ['06', 'Preparación laboral', 'Construye tu perfil, CV y confianza para nuevas oportunidades.']] as [$number, $title, $description])
        <article class="feature"><span class="number">{{ $number }}</span><h2>{{ $title }}</h2><p>{{ $description }}</p></article>
    @endforeach
</section>
@endsection
