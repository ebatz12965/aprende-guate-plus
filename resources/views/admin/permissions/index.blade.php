@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-8"><p class="text-sm font-bold text-blue-700">CONTROL DE ACCESO</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Gestión de permisos</h1><p class="mt-2 text-slate-500">Configura los permisos disponibles en la plataforma.</p></div>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><permissions-component></permissions-component></section>
</div>
@endsection
