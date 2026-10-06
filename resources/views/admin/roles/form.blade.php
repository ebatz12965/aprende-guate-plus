@extends('layouts.admin')

@section('content')
@use('App\Support\AdminAccess')
@php($editing = $role->exists)
<div class="max-w-3xl">
    <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $editing ? 'Editar rol' : 'Crear rol' }}</h1>
            <p class="mt-2 text-slate-600">Elige solo lo que la persona necesita para su tarea. Quien tenga «Asignar roles» solo podrá dar roles cuyos permisos también tenga.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-xl bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Volver a roles</a>
    </div>

    <form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div>
            <label for="name" class="block text-sm font-semibold text-slate-700">Nombre del rol</label>
            <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" maxlength="60" placeholder="Por ejemplo: Soporte" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            @error('name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>

        <fieldset>
            <legend class="text-sm font-semibold text-slate-700">Permisos</legend>
            @error('permissions')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
            <div class="mt-3 space-y-5">
                @foreach (AdminAccess::delegableByGroup() as $group => $permissions)
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ $group }}</p>
                        <div class="mt-2 grid gap-2">
                            @foreach ($permissions as $permission => $description)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 hover:border-blue-300">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500" @checked(in_array($permission, old('permissions', $selected), true))>
                                    <span class="text-sm leading-6 text-slate-700">{{ $description }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </fieldset>

        @if ($editing && $role->users()->count() > 0)
            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">Lo tienen {{ $role->users()->count() }} {{ $role->users()->count() === 1 ? 'cuenta' : 'cuentas' }}: el cambio aplica de inmediato.</p>
        @endif

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
            <a href="{{ route('admin.roles.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</a>
            <button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">{{ $editing ? 'Guardar cambios' : 'Crear rol' }}</button>
        </div>
    </form>
</div>
@endsection
