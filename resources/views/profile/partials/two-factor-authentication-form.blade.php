<section x-data="{ authenticatorOpen: {{ $twoFactorSetupSecret || $user->hasTwoFactorEnabled() ? 'true' : 'false' }}, setupModalOpen: {{ $openTwoFactorSetup ? 'true' : 'false' }}, setupStep: '{{ $errors->has('code') ? 'code' : 'qr' }}' }" @keydown.escape.window="setupModalOpen = false">
    <header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h2 class="text-xl font-extrabold text-slate-900">Verificación en dos pasos</h2><p class="mt-2 text-sm leading-6 text-slate-600">Administra tu aplicación autenticadora y los códigos de recuperación de tu cuenta.</p></div><span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-extrabold {{ $user->hasTwoFactorEnabled() ? 'bg-emerald-100 text-emerald-800' : 'bg-orange-100 text-orange-800' }}">{{ $user->hasTwoFactorEnabled() ? 'Activa' : 'Pendiente' }}</span></div>
    </header>

    @if ($twoFactorRecoveryCodes)
        <div x-data="{ copied: false }" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <h3 class="font-extrabold text-amber-900">Guarda tus códigos de recuperación</h3>
            <p class="mt-1 text-sm leading-6 text-amber-800">Se muestran una sola vez. Úsalos si pierdes acceso a tu aplicación autenticadora; cada uno sirve una sola vez.</p>
            <div class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm text-amber-950 sm:grid-cols-4">@foreach ($twoFactorRecoveryCodes as $recoveryCode)<code class="rounded-lg bg-white px-3 py-2 text-center ring-1 ring-amber-200">{{ $recoveryCode }}</code>@endforeach</div>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <button type="button" @click="navigator.clipboard?.writeText(@js(implode("\n", $twoFactorRecoveryCodes))).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-sm font-extrabold text-amber-900 transition hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                    <span x-text="copied ? 'Copiados' : 'Copiar códigos'">Copiar códigos</span>
                </button>
                <a href="{{ route('two-factor.recovery-codes.download') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 20h14"/></svg>
                    Descargar PDF
                </a>
            </div>
            <p class="mt-2 text-xs leading-5 text-amber-800" aria-live="polite"><span x-show="copied" x-cloak>Pégalos en tu gestor de contraseñas. </span>El PDF puede descargarse durante los próximos 15 minutos.</p>
        </div>
    @endif

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
        <button type="button" @click="authenticatorOpen = !authenticatorOpen" class="flex w-full items-center justify-between gap-4 bg-white px-5 py-4 text-left transition hover:bg-slate-50"><span class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-100 text-lg text-blue-800">🔐</span><span><span class="block font-extrabold text-slate-900">Aplicación autenticadora</span><span class="mt-1 block text-sm text-slate-500">Google Authenticator, Microsoft Authenticator, Authy u otra compatible</span></span></span><span class="text-xl text-slate-500" x-text="authenticatorOpen ? '−' : '+'"></span></button>

        <div x-show="authenticatorOpen" x-transition x-cloak class="border-t border-slate-200 bg-slate-50/70 p-5">
            @if ($user->hasTwoFactorEnabled())
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><div class="flex items-start gap-3"><span class="mt-0.5 text-emerald-700">✓</span><div><p class="font-extrabold text-emerald-900">Aplicación autenticadora registrada</p><p class="mt-1 text-sm text-emerald-800">Autenticada {{ $user->two_factor_confirmed_at?->isToday() ? 'hoy a las '.$user->two_factor_confirmed_at->format('H:i') : 'hace '.max(1, (int) $user->two_factor_confirmed_at?->diffInDays(now())).' días' }}.</p></div></div></div>
                <p class="mt-4 text-sm leading-6 text-slate-600">Puedes cambiar la aplicación registrada. Al hacerlo se invalidará la anterior y tendrás que confirmar la nueva.</p>
                <form method="POST" action="{{ route('two-factor.setup') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">@csrf<div class="flex-1"><x-input-label for="two_factor_change_password" value="Confirma tu contraseña para cambiarla" class="mb-2 text-sm font-bold text-slate-700"/><x-text-input id="two_factor_change_password" name="current_password" type="password" class="block w-full rounded-xl border-slate-200 px-4 py-3" required autocomplete="current-password"/><x-input-error :messages="$errors->get('current_password')" class="mt-2"/></div><button class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-extrabold text-white hover:bg-blue-800">Cambiar aplicación</button></form>
            @elseif (! $user->has_local_password)
                <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm leading-6 text-orange-900"><strong>No hay ninguna aplicación registrada.</strong> Tu cuenta se creó con Google: primero crea una contraseña en la sección «Contraseña» de esta página y luego vuelve aquí para activar la verificación en dos pasos.</div>
            @else
                <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm leading-6 text-orange-900"><strong>No hay ninguna aplicación registrada.</strong> Añade una para proteger tu cuenta.</div>
                <form method="POST" action="{{ route('two-factor.setup') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">@csrf<div class="flex-1"><x-input-label for="two_factor_setup_password" value="Confirma tu contraseña para comenzar" class="mb-2 text-sm font-bold text-slate-700"/><x-text-input id="two_factor_setup_password" name="current_password" type="password" class="block w-full rounded-xl border-slate-200 px-4 py-3" required autocomplete="current-password"/><x-input-error :messages="$errors->get('current_password')" class="mt-2"/></div><button class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-extrabold text-white hover:bg-blue-800">Añadir aplicación</button></form>
            @endif
        </div>
    </div>

    @if ($user->hasTwoFactorEnabled())
        @php($recoveryRemaining = count($user->two_factor_recovery_codes ?? []))
        <div x-data="{ open: {{ $errors->recoveryCodes->any() ? 'true' : 'false' }} }" class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
            <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-4 bg-white px-5 py-4 text-left transition hover:bg-slate-50"><span class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-100 text-blue-800" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M17 6l3 3M15 8l2 2"/></svg></span><span><span class="block font-extrabold text-slate-900">Códigos de recuperación</span><span class="mt-1 block text-sm {{ $recoveryRemaining <= 2 ? 'font-bold text-orange-800' : 'text-slate-500' }}">{{ $recoveryRemaining === 1 ? 'Te queda 1 código disponible' : "Te quedan {$recoveryRemaining} códigos disponibles" }} de 8</span></span></span><span class="text-xl text-slate-500" x-text="open ? '−' : '+'"></span></button>
            <div x-show="open" x-transition x-cloak class="border-t border-slate-200 bg-slate-50/70 p-5">
                <p class="text-sm leading-6 text-slate-600">Te sirven para entrar si pierdes tu teléfono o cambias de aplicación. Por seguridad no podemos volver a mostrar los actuales: si no los tienes a mano o te quedan pocos, genera códigos nuevos y los anteriores dejarán de servir.</p>
                @if ($recoveryRemaining <= 2)
                    <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm leading-6 text-orange-900"><strong>Te quedan pocos códigos.</strong> Genera códigos nuevos para no quedarte sin forma de recuperar tu cuenta.</div>
                @endif
                <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">@csrf<div class="flex-1"><x-input-label for="recovery_codes_password" value="Confirma tu contraseña para generarlos" class="mb-2 text-sm font-bold text-slate-700"/><x-text-input id="recovery_codes_password" name="current_password" type="password" class="block w-full rounded-xl border-slate-200 px-4 py-3" required autocomplete="current-password"/><x-input-error :messages="$errors->recoveryCodes->get('current_password')" class="mt-2"/></div><button class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-extrabold text-white hover:bg-blue-800">Generar códigos nuevos</button></form>
            </div>
        </div>
    @endif

    @if ($twoFactorSetupSecret)
        <div x-show="setupModalOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/55 p-4" role="dialog" aria-modal="true" aria-labelledby="authenticator-setup-title">
            <section @click.outside="setupModalOpen = false; window.history.replaceState({}, '', '{{ route('profile.edit', ['section' => 'security']) }}')" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
                <div class="flex items-start justify-between gap-4"><div><h3 id="authenticator-setup-title" class="text-2xl font-extrabold text-slate-900" x-text="setupStep === 'qr' ? 'Escanea el código QR' : 'Confirma tu aplicación'"></h3></div><button type="button" @click="setupModalOpen = false; window.history.replaceState({}, '', '{{ route('profile.edit', ['section' => 'security']) }}')" class="rounded-lg p-1 text-slate-500 hover:bg-slate-100" aria-label="Cerrar">✕</button></div>
                <div x-show="setupStep === 'qr'" class="mt-5"><p class="text-sm leading-6 text-slate-600">Abre tu aplicación autenticadora y escanea este código. La información se genera localmente y no se envía a ningún servicio externo.</p><div class="mt-5 flex justify-center rounded-xl border border-slate-200 bg-white p-4"><img src="{{ $twoFactorQrCode }}" alt="Código QR para configurar la aplicación autenticadora" class="h-64 w-64" /></div><details class="mt-4"><summary class="cursor-pointer text-sm font-bold text-blue-700">No puedo escanearlo</summary><p class="mt-2 text-sm leading-6 text-slate-600">Usa la configuración manual con esta clave:</p><code class="mt-2 block break-all rounded-lg bg-slate-100 px-3 py-2 font-mono text-sm font-bold tracking-wider text-slate-900">{{ $twoFactorSetupSecret }}</code></details><button type="button" @click="setupStep = 'code'" class="mt-6 w-full rounded-xl bg-blue-700 px-4 py-3 text-sm font-extrabold text-white hover:bg-blue-800">Siguiente</button></div>
                <div x-show="setupStep === 'code'" x-cloak class="mt-5"><p class="text-sm leading-6 text-slate-600">Escribe el código de seis dígitos que aparece en tu aplicación para verificar que quedó vinculada.</p><form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-5 space-y-4">@csrf<div><x-input-label for="two_factor_confirmation_code" value="Código de verificación" class="mb-2 text-sm font-bold text-slate-700"/><x-text-input id="two_factor_confirmation_code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" class="block w-full rounded-xl border-slate-200 px-4 py-3 text-center text-xl tracking-[.4em]" placeholder="123456" required autocomplete="one-time-code" autofocus/><x-input-error :messages="$errors->get('code')" class="mt-2"/></div><div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between"><button type="button" @click="setupStep = 'qr'" class="rounded-xl px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100">Atrás</button><button class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-extrabold text-white hover:bg-blue-800">Verificar y activar</button></div></form></div>
            </section>
        </div>
    @endif
</section>