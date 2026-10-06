@extends('layouts.admin')

@section('content')
<div class="max-w-3xl">
    <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Crear cuenta</h1>
            <p class="mt-2 text-slate-600">La persona podrá entrar con este correo y contraseña. Todas las cuentas incluyen el espacio de aprendizaje.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Volver a usuarios</a>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <div><label for="name" class="block text-sm font-semibold text-slate-700">Nombre completo</label><input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="off" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>@error('name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="email" class="block text-sm font-semibold text-slate-700">Correo electrónico</label><input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="off" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>@error('email')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
        <div class="grid gap-6 sm:grid-cols-2">
            <div><label for="password" class="block text-sm font-semibold text-slate-700">Contraseña</label><input type="password" name="password" id="password" autocomplete="new-password" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>@error('password')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirmar contraseña</label><input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required></div>
        </div>

        @if ($assignable)
            <div class="border-t border-slate-200 pt-6">
                @include('admin.users.partials.roles', ['assignable' => $assignable, 'checked' => old('roles', [])])
                @error('roles')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        @endif

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
            <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</a>
            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Crear cuenta</button>
        </div>
    </form>
</div>
@endsection
