<x-guest-layout>
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Crea tu contraseña</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">Entraste con una contraseña temporal. Por seguridad, elige una nueva antes de continuar; la temporal dejará de funcionar.</p>

    <form method="POST" action="{{ route('password.change.update') }}" class="mt-7 space-y-5">
        @csrf
        @method('PUT')
        <div>
            <x-input-label for="current_password" value="Contraseña temporal" />
            <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
            <p class="mt-1 text-xs text-slate-500">Usa al menos 8 caracteres. Una frase larga es más segura y fácil de recordar.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Repite la nueva contraseña" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
        </div>
        <button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700">Guardar y continuar</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button class="text-sm font-bold text-slate-600 underline-offset-4 hover:underline">Cerrar sesión</button>
    </form>
</x-guest-layout>
