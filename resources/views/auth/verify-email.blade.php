<x-guest-layout>
    <div class="rounded-2xl border border-sky-100 bg-sky-50 p-6 shadow-sm">
        <div class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-sky-700 text-white">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="m4 7 8 6 8-6"/></svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Confirma tu correo electrónico</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Enviamos un enlace de confirmación a <strong class="text-slate-800">{{ auth()->user()->email }}</strong>. Ábrelo para activar tu cuenta y acceder a la plataforma.
        </p>
    </div>

    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
        Tu cuenta está pendiente de confirmación. Hasta verificar el correo no tendrás acceso a cursos, rutas ni funciones de la plataforma.
    </div>

    @if (session('error'))
        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium leading-6 text-rose-800" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if (session('status') === 'verification-link-sent')
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium leading-6 text-emerald-800" role="status">
            Te enviamos un nuevo enlace de confirmación. Revisa tu bandeja de entrada y la carpeta de spam.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <x-primary-button class="w-full justify-center">
            Reenviar enlace de confirmación
        </x-primary-button>
    </form>

    <div class="mt-6 border-t border-slate-200 pt-5 text-center">
        <p class="text-sm text-slate-500">¿No eres tú o quieres cambiar de cuenta?</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="text-sm font-semibold text-sky-700 underline decoration-sky-300 underline-offset-4 hover:text-sky-900">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>
