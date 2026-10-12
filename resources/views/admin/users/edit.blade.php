@extends('layouts.admin')

@section('content')
@use('App\Support\AdminAccess')
@php
    $canEditDetails = ! $protection && auth()->user()->can('usuarios.gestionar');
    $canEditRoles = ! $protection && auth()->user()->can('roles.asignar');
    $canSuspend = ! $protection && auth()->user()->can('usuarios.suspender') && ! auth()->user()->is($user);
    $suspended = $user->isSuspended();
    $input = 'mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-500';
@endphp
<div class="max-w-[1200px]">
    <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div class="flex items-center gap-4">
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-blue-100 text-xl font-extrabold text-blue-800" aria-hidden="true">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <h1 class="truncate text-3xl font-extrabold tracking-tight text-slate-900">{{ $user->name }}</h1>
                <p class="mt-1 truncate text-slate-600">{{ $user->email }} · Cuenta creada el {{ $user->created_at->format('d/m/Y') }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if ($suspended)
                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800">Suspendida</span>
                    @else
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800 ring-1 ring-inset ring-emerald-200">Activa</span>
                    @endif
                    @foreach ($user->roles->sortBy('name') as $role)
                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-slate-700 ring-1 ring-inset ring-slate-200">{{ AdminAccess::roleLabel($role->name) }}</span>
                    @endforeach
                    @if ($user->hasTwoFactorEnabled())
                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-800 ring-1 ring-inset ring-blue-200">2FA activa</span>
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-xl bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Volver a usuarios</a>
    </div>

    @if ($protection)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">{{ $protection }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <div class="space-y-6">
            {{-- Datos --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="datos">
                <h2 id="datos" class="text-lg font-extrabold text-slate-900">Datos de la cuenta</h2>
                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="mt-5 space-y-5">
                    @csrf @method('PUT')
                    <fieldset @disabled(! $canEditDetails) class="space-y-5">
                        <div><label for="name" class="block text-sm font-semibold text-slate-700">Nombre completo</label><input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="{{ $input }}" required>@error('name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="email" class="block text-sm font-semibold text-slate-700">Correo electrónico</label><input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="{{ $input }}" required>@error('email')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        @if ($canEditDetails)
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div><label for="password" class="block text-sm font-semibold text-slate-700">Nueva contraseña <span class="font-normal text-slate-500">(opcional)</span></label><input type="password" name="password" id="password" autocomplete="new-password" class="{{ $input }}">@error('password')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                                <div><label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirmar contraseña</label><input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="{{ $input }}"></div>
                            </div>
                        @endif
                    </fieldset>
                    @if ($canEditDetails)
                        <div class="flex justify-end"><button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Guardar datos</button></div>
                    @endif
                </form>
            </section>

            {{-- Roles --}}
            @can('roles.asignar')
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="roles-titulo">
                    <h2 id="roles-titulo" class="text-lg font-extrabold text-slate-900">Roles</h2>
                    <form action="{{ route('admin.users.roles', $user) }}" method="POST" class="mt-1 space-y-5">
                        @include('admin.users.partials.roles', ['assignable' => $assignable, 'checked' => old('roles', $user->getRoleNames()->all()), 'locked' => ! $canEditRoles, 'legend' => false])
                        @csrf @method('PUT')
                        @error('roles')<p class="text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                        @if ($canEditRoles)
                            <p class="text-sm text-slate-500">Al asignar Mentor se crea su perfil de mentor sin pasar por la solicitud; queda registrado en la bitácora.</p>
                            <div class="flex justify-end"><button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Guardar roles</button></div>
                        @endif
                    </form>
                </section>
            @endcan
        </div>

        <div class="space-y-6">
            {{-- Estado --}}
            <section class="rounded-2xl border {{ $suspended ? 'border-rose-200' : 'border-slate-200' }} bg-white p-6 shadow-sm" aria-labelledby="estado">
                <h2 id="estado" class="text-lg font-extrabold text-slate-900">Estado de la cuenta</h2>
                @if ($suspended)
                    <div class="mt-4 rounded-xl bg-rose-50 p-4 text-sm text-rose-900">
                        <p class="font-bold">Suspendida {{ $user->suspended_until ? 'hasta el '.$user->suspended_until->format('d/m/Y H:i') : 'de forma indefinida' }}</p>
                        <p class="mt-1">Desde el {{ $user->suspended_at->format('d/m/Y H:i') }}{{ $user->suspendedBy ? ' · por '.$user->suspendedBy->name : '' }}</p>
                        @if ($user->suspension_reason)<p class="mt-2"><span class="font-semibold">Motivo:</span> {{ $user->suspension_reason }}</p>@endif
                    </div>
                    @if ($canSuspend)
                        <form action="{{ route('admin.users.reactivate', $user) }}" method="POST" class="mt-4">
                            @csrf @method('DELETE')
                            <button class="inline-flex min-h-11 items-center rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white hover:bg-emerald-800">Reactivar cuenta</button>
                        </form>
                    @endif
                @else
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Mientras esté suspendida, la persona no puede entrar (tampoco con Google), su sesión abierta se cierra, no recibe avisos ni correos, su perfil público se oculta y sus mentorías abiertas se cancelan con aviso a la otra persona. Sus datos se conservan.
                    </p>
                    @if ($canSuspend)
                        <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="mt-5 space-y-4" x-data="{ duration: @js(old('duration', '7')) }">
                            @csrf
                            <fieldset>
                                <legend class="text-sm font-semibold text-slate-700">Duración</legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach (['1' => '1 día', '3' => '3 días', '7' => '7 días', '30' => '30 días', 'custom' => 'Hasta una fecha', 'permanent' => 'Indefinida'] as $value => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="duration" value="{{ $value }}" x-model="duration" class="peer sr-only">
                                            <span class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 peer-checked:border-rose-300 peer-checked:bg-rose-50 peer-checked:text-rose-900 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div x-show="duration === 'custom'" x-cloak>
                                <label for="until" class="block text-sm font-semibold text-slate-700">Suspender hasta</label>
                                <input type="datetime-local" name="until" id="until" value="{{ old('until') }}" class="{{ $input }}" :required="duration === 'custom'">
                                @error('until')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="reason" class="block text-sm font-semibold text-slate-700">Motivo <span class="font-normal text-slate-500">(la persona lo verá al intentar entrar)</span></label>
                                <textarea name="reason" id="reason" rows="3" maxlength="500" required class="{{ $input }}" placeholder="Por ejemplo: incumplimiento de las normas de convivencia en mentorías.">{{ old('reason') }}</textarea>
                                @error('reason')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            @error('suspension')<p class="text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                            <button class="inline-flex min-h-11 items-center rounded-xl bg-rose-700 px-5 text-sm font-bold text-white hover:bg-rose-800">Suspender cuenta</button>
                        </form>
                    @endif
                @endif
            </section>

            {{-- Historial --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="historial">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="historial" class="text-lg font-extrabold text-slate-900">Historial de la cuenta</h2>
                    @can('bitacora.ver')<a href="{{ route('admin.audit.index', ['cuenta' => $user->id]) }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Ver en la bitácora →</a>@endcan
                </div>
                <ol class="mt-4 space-y-4">
                    @forelse ($history as $activity)
                        <li class="border-l-2 border-slate-200 pl-4">
                            <p class="text-sm font-bold text-slate-900">{{ $activity->description }}</p>
                            <p class="text-xs text-slate-500">{{ $activity->created_at->format('d/m/Y H:i') }} · {{ $activity->causer?->name ?? 'Sistema' }}</p>
                            @include('admin.partials.activity-properties', ['activity' => $activity])
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Sin cambios administrativos registrados.</li>
                    @endforelse
                </ol>
            </section>

            {{-- Eliminar --}}
            @can('usuarios.eliminar')
                @unless (auth()->user()->is($user))
                    <details class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm" @if ($errors->has('confirm_email') || $errors->has('delete')) open @endif>
                        <summary class="cursor-pointer text-lg font-extrabold text-rose-800">Eliminar definitivamente</summary>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Borra la cuenta y todos sus datos: diagnósticos, rutas, entregas, conversaciones y mentorías (también las de las personas con quienes las tuvo). No se puede deshacer. Úsalo solo ante una solicitud de borrado de datos; para todo lo demás, suspende.</p>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="mt-4 space-y-3">
                            @csrf @method('DELETE')
                            <label for="confirm_email" class="block text-sm font-semibold text-slate-700">Escribe <span class="font-mono">{{ $user->email }}</span> para confirmar</label>
                            <input type="text" name="confirm_email" id="confirm_email" autocomplete="off" class="{{ $input }}" required>
                            @error('confirm_email')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                            @error('delete')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                            <button class="inline-flex min-h-11 items-center rounded-xl border border-rose-300 bg-white px-5 text-sm font-bold text-rose-800 hover:bg-rose-50">Eliminar cuenta y datos</button>
                        </form>
                    </details>
                @endunless
            @endcan
        </div>
    </div>
</div>
@endsection
