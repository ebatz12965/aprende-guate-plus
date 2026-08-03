@extends('layouts.platform')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-sky-700">Administración</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Editar usuario</h1>
            <p class="mt-2 text-sm text-slate-500">Actualiza sus datos y define el nivel de acceso a la plataforma.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Volver a usuarios</a>
    </div>

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <div><label for="name" class="block text-sm font-semibold text-slate-700">Nombre completo</label><input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>@error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="email" class="block text-sm font-semibold text-slate-700">Correo electrónico</label><input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>@error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="role" class="block text-sm font-semibold text-slate-700">Rol de acceso</label><select name="role" id="role" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(old('role', $user->getRoleNames()->first()) === $role->name)>{{ $role->name }}</option>@endforeach</select><p class="mt-2 text-sm text-slate-500">Admin gestiona la plataforma; Instructor imparte cursos; Student accede a sus cursos inscritos.</p>@error('role')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="border-t border-slate-200 pt-6"><label for="password" class="block text-sm font-semibold text-slate-700">Nueva contraseña <span class="font-normal text-slate-400">(opcional)</span></label><input type="password" name="password" id="password" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" autocomplete="new-password">@error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirmar nueva contraseña</label><input type="password" name="password_confirmation" id="password_confirmation" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" autocomplete="new-password"></div>
            <div class="flex justify-end gap-3 pt-2"><a href="{{ route('admin.users.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</a><button type="submit" class="rounded-xl bg-sky-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-sky-800">Guardar cambios</button></div>
        </form>
    </div>
@endsection
