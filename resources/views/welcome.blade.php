@extends('layouts.platform')

@section('content')
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-800 to-violet-700 px-6 py-16 text-white shadow-xl sm:px-12 lg:px-16 lg:py-24">
    <div class="absolute inset-0 opacity-40" style="background-image: radial-gradient(circle at 88% 18%, #67e8f9 0, transparent 24%), radial-gradient(circle at 70% 90%, #c4b5fd 0, transparent 20%);"></div>
    <div class="relative max-w-3xl"><p class="mb-5 inline-flex items-center gap-2 rounded-full border border-indigo-300/30 bg-white/10 px-4 py-2 text-sm font-semibold text-indigo-100"><span class="h-2 w-2 rounded-full bg-emerald-300"></span> Aprendizaje que se adapta a ti</p><h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">Construye habilidades para tu futuro con IA.</h1><p class="mt-6 max-w-2xl text-lg leading-8 text-indigo-100/90">Diagnóstico, ruta personalizada, Tutor IA, retos prácticos y mentoría profesional en una sola plataforma para Guatemala.</p><div class="mt-10 flex flex-wrap gap-4">@auth <a href="{{ route('adaptive.dashboard') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-900 shadow-lg transition hover:bg-indigo-50">Ir a mi panel</a> @else <a href="{{ route('register') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-900 shadow-lg transition hover:bg-indigo-50">Comenzar mi ruta</a><a href="{{ route('login') }}" class="rounded-xl border border-white/30 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">Iniciar sesión</a> @endauth</div></div>
</section>
<section class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
 @foreach ([['01','Diagnóstico inteligente','Conoce tu nivel actual y las habilidades que puedes fortalecer.'],['02','Ruta personalizada','Recibe pasos concretos según tu objetivo profesional.'],['03','Tutor IA','Pregunta, practica y recibe guía en cualquier momento.'],['04','Retos prácticos','Aplica lo aprendido en situaciones cercanas al trabajo real.'],['05','Mentoría profesional','Conecta con personas que te ayudan a avanzar.'],['06','Preparación laboral','Construye tu perfil, CV y confianza para nuevas oportunidades.']] as [$number,$title,$description])
 <article class="platform-feature"><span class="platform-feature-icon">{{ $number }}</span><h2>{{ $title }}</h2><p>{{ $description }}</p></article>
 @endforeach
</section>
@endsection
