@extends('layouts.admin')

@section('content')
<div class="max-w-6xl">
    <div class="mb-8">
        <p class="text-sm font-bold text-blue-700">RESUMEN OPERATIVO</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Panel administrativo</h1>
        <p class="mt-2 text-slate-500">Controla usuarios, contenido, inteligencia artificial y servicios de Aprende Guate.</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <section class="admin-stat-card"><p>Usuarios registrados</p><strong>{{ $users }}</strong><span>Personas con una cuenta en la plataforma</span></section>
        
        <section class="admin-stat-card"><p>Configuración</p><strong>IA y correo</strong><span>Ajusta límites de uso y servicios salientes</span><a href="{{ route('admin.ai-settings.edit') }}">Ver configuración →</a></section>
    </div>
</div>
@endsection
