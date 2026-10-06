@extends('layouts.admin')

@section('content')
@use('App\Support\AdminAccess')
@php
    $roleChip = fn (string $role) => match ($role) {
        AdminAccess::SUPER_ROLE => 'bg-violet-50 text-violet-800 ring-violet-200',
        AdminAccess::MENTOR_ROLE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        AdminAccess::BASE_ROLE => 'bg-slate-100 text-slate-700 ring-slate-200',
        default => 'bg-blue-50 text-blue-800 ring-blue-200',
    };
@endphp
<div class="max-w-[1500px]">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Usuarios</h1>
            <p class="mt-2 text-slate-600">Busca cuentas, revisa sus roles y su estado. Suspende en lugar de eliminar: la cuenta y su historial se conservan.</p>
        </div>
        @can('usuarios.gestionar')
            <a href="{{ route('admin.users.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-4 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Crear cuenta</a>
        @endcan
    </div>

    <form method="GET" class="mt-7 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-end">
        <div class="flex-1">
            <label for="q" class="block text-sm font-semibold text-slate-700">Buscar</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre o correo" class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label for="estado" class="block text-sm font-semibold text-slate-700">Estado</label>
            <select id="estado" name="estado" class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 lg:w-44">
                <option value="">Todas</option>
                <option value="activas" @selected(($filters['estado'] ?? '') === 'activas')>Activas</option>
                <option value="suspendidas" @selected(($filters['estado'] ?? '') === 'suspendidas')>Suspendidas ({{ $suspendedCount }})</option>
            </select>
        </div>
        <div>
            <label for="rol" class="block text-sm font-semibold text-slate-700">Rol</label>
            <select id="rol" name="rol" class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 lg:w-52">
                <option value="">Todos</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(($filters['rol'] ?? '') === $role)>{{ AdminAccess::roleLabel($role) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-800">Filtrar</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">Limpiar</a>
            @endif
        </div>
    </form>

    <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($users->total() > 0)
            <div class="relative overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-500 sm:px-6">Cuenta</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-500">Roles</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-500">Estado</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold text-slate-500">Registro</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $user)
                            <tr class="{{ $user->isSuspended() ? 'bg-rose-50/40' : '' }}">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-100 font-extrabold text-blue-800" aria-hidden="true">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-slate-900">{{ $user->name }}</p>
                                            <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($user->roles->sortBy('name') as $role)
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $roleChip($role->name) }}">{{ AdminAccess::roleLabel($role->name) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    @if ($user->isSuspended())
                                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800">Suspendida</span>
                                        <p class="mt-1 text-xs text-slate-500">{{ $user->suspended_until ? 'Hasta el '.$user->suspended_until->format('d/m/Y') : 'Indefinida' }}</p>
                                    @elseif (! $user->email_verified_at)
                                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800">Correo sin verificar</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800">Activa</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-bold text-blue-700 hover:bg-blue-50">Abrir<span class="sr-only"> la cuenta de {{ $user->name }}</span></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">{{ array_filter($filters) ? 'Ninguna cuenta coincide con los filtros.' : 'No hay usuarios registrados.' }}</div>
        @endif
    </section>
</div>
@endsection
