@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <p class="text-sm font-bold text-blue-700">CONTROL DE ACCESO</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Roles del sistema</h1>
        <p class="mt-2 text-slate-500">Consulta los roles disponibles para organizar los permisos de Aprende Guate.</p>
    </div>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Rol</th><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Creado</th><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Actualizado</th></tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">@forelse($roles as $role)<tr><td class="px-6 py-4"><span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-bold text-blue-800">{{ $role->name }}</span></td><td class="px-6 py-4 text-sm text-slate-600">{{ $role->created_at->format('d/m/Y H:i') }}</td><td class="px-6 py-4 text-sm text-slate-600">{{ $role->updated_at->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="3" class="px-6 py-10 text-center text-slate-500">No hay roles registrados.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </section>
</div>
@endsection
