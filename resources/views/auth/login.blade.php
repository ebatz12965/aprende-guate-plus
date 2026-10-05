<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">Inicia sesión</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Continúa con tu ruta, tus prácticas y tu crecimiento profesional.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" value="Correo electrónico" class="mb-2 text-sm font-bold text-slate-700" />
            <x-text-input id="email" class="block w-full rounded-xl border-slate-200 px-4 py-3 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600" type="email" name="email" :value="old('email')" placeholder="tu-correo@ejemplo.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" class="mb-2 text-sm font-bold text-slate-700" />
            <x-text-input id="password" class="block w-full rounded-xl border-slate-200 px-4 py-3 text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600" type="password" name="password" required autocomplete="current-password" />
            @if (Route::has('password.request'))<div class="mt-2 text-right"><a class="text-sm font-bold text-blue-700 transition hover:text-blue-950" href="{{ route('password.request') }}">¿La olvidaste?</a></div>@endif
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600"><input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" name="remember"> Recordar mi sesión</label>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">Ingresar a la plataforma<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
    </form>

    <div class="my-7 flex items-center gap-3"><div class="h-px flex-1 bg-slate-200"></div><span class="text-xs font-semibold text-slate-500">O continúa con</span><div class="h-px flex-1 bg-slate-200"></div></div>

    <a href="{{ route('socialite.redirect', 'google') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"><svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.8 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.5a4.7 4.7 0 0 1-2 3.1v2.5h3.2c1.9-1.8 3.1-4.4 3.1-7.4Z"/><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.2-2.5c-.9.6-2 .9-3.5.9-2.7 0-5-1.8-5.8-4.3H2.9v2.6A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.2 13.7a6 6 0 0 1 0-3.4V7.7H2.9A10 10 0 0 0 2 12c0 1.5.4 2.9.9 4.3l3.3-2.6Z"/><path fill="#EA4335" d="M12 6c1.5 0 2.8.5 3.8 1.5l2.9-2.9A10 10 0 0 0 2.9 7.7l3.3 2.6C7 7.8 9.3 6 12 6Z"/></svg>Continuar con Google</a>

    @if (Route::has('register'))<p class="mt-8 text-center text-sm text-slate-500">¿Aún no tienes una cuenta? <a href="{{ route('register') }}" class="font-extrabold text-blue-700 hover:text-blue-950">Crear cuenta</a></p>@endif
</x-guest-layout>
