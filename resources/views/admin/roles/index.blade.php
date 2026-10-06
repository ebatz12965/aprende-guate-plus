@extends('layouts.admin')

@section('content')
@use('App\Support\AdminAccess')
<div class="max-w-5xl">
    <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Roles</h1>
            <p class="mt-2 text-slate-600">Cada cuenta puede tener varios roles. Los roles personalizados agrupan permisos de administración para delegar tareas sin entregar todo el panel.</p>
        </div>
        @can('roles.gestionar')
            <a href="{{ route('admin.roles.create') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-xl bg-blue-700 px-4 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Crear rol</a>
        @endcan
    </div>

    <div class="space-y-4">
        @foreach ($roles as $role)
            @php($system = AdminAccess::isSystemRole($role->name))
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-extrabold text-slate-900">{{ AdminAccess::roleLabel($role->name) }}</h2>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $system ? 'bg-slate-100 text-slate-700' : 'bg-blue-50 text-blue-800' }}">{{ $system ? 'Del sistema' : 'Personalizado' }}</span>
                            <span class="text-sm text-slate-500">{{ $role->users_count }} {{ $role->users_count === 1 ? 'cuenta' : 'cuentas' }}</span>
                        </div>
                        @if ($system)
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ AdminAccess::roleDescription($role->name) }}</p>
                        @else
                            <ul class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($role->permissions as $permission)
                                    <li class="rounded-lg bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">{{ AdminAccess::DELEGABLE[$permission->name][1] ?? $permission->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if (! $system)
                        @can('roles.gestionar')
                            <div class="flex shrink-0 gap-2">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-bold text-blue-700 hover:bg-blue-50">Editar</a>
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" x-data="{ armed: false }" @submit="if (! armed) { $event.preventDefault(); armed = true; setTimeout(() => armed = false, 4000); }">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-bold text-rose-700 hover:bg-rose-50" x-text="armed ? '¿Eliminar? Confirma' : 'Eliminar'">Eliminar</button>
                                </form>
                            </div>
                        @endcan
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
        <h2 class="font-extrabold text-slate-900">Solo el super administrador</h2>
        <p class="mt-1 text-sm text-slate-600">Estas tareas no se pueden delegar a un rol personalizado porque manejan credenciales, gasto de IA o el propio control de acceso.</p>
        <ul class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
            @foreach (AdminAccess::SUPER_ONLY as $description)
                <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400" aria-hidden="true"></span>{{ $description }}</li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
