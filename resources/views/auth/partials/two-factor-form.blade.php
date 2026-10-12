{{-- Segundo paso del inicio de sesión: seis casillas para la app autenticadora o un código de recuperación. --}}
@php
    $codeError = $errors->first('code');
    $locked = (bool) session('two_factor_locked');
    $remaining = session('two_factor_remaining');
@endphp
<div data-two-factor class="text-left">
    <div class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-sky-300" aria-hidden="true">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.4 7.5 9.5 4.4-1.1 7.5-4.9 7.5-9.5V6L12 3Z"/><path d="m8.8 12.2 2.2 2.2 4.4-4.6"/></svg>
    </div>
    <h2 id="two-factor-title" class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-50">Confirma que eres tú</h2>
    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Tu cuenta tiene verificación en dos pasos. Escribe el código de seis dígitos que muestra tu aplicación autenticadora.</p>

    <div class="mt-5 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
        <span class="grid h-9 w-9 flex-none place-items-center rounded-xl bg-blue-50 font-extrabold text-blue-800 dark:bg-blue-500/15 dark:text-sky-300" aria-hidden="true">{{ $challengeAccount['initial'] }}</span>
        <p class="min-w-0 text-sm font-bold text-slate-900 dark:text-slate-50">
            <span class="block truncate">{{ $challengeAccount['name'] }}</span>
            <span class="block truncate text-[13px] font-medium text-slate-500 dark:text-slate-400">{{ $challengeAccount['email'] }}</span>
        </p>
        <form method="POST" action="{{ route('two-factor.challenge.cancel') }}" class="ml-auto flex-none">
            @csrf
            <button type="submit" class="rounded-lg px-1 text-[13px] font-bold text-blue-800 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-sky-300">No soy yo</button>
        </form>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-1 rounded-2xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-950" role="tablist" aria-label="Forma de verificar">
        <button type="button" role="tab" id="two-factor-tab-app" aria-controls="two-factor-panel-app" aria-selected="true" data-two-factor-tab="app"
            class="rounded-xl px-2 py-2.5 text-sm font-bold text-slate-600 transition aria-selected:bg-white aria-selected:text-slate-900 aria-selected:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-slate-300 dark:aria-selected:bg-slate-800 dark:aria-selected:text-slate-50">App autenticadora</button>
        <button type="button" role="tab" id="two-factor-tab-rec" aria-controls="two-factor-panel-rec" aria-selected="false" data-two-factor-tab="rec"
            class="rounded-xl px-2 py-2.5 text-sm font-bold text-slate-600 transition aria-selected:bg-white aria-selected:text-slate-900 aria-selected:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-slate-300 dark:aria-selected:bg-slate-800 dark:aria-selected:text-slate-50">Código de recuperación</button>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge.verify') }}" data-two-factor-form novalidate>
        @csrf
        <input type="hidden" name="code" value="">

        <div class="mt-5" id="two-factor-panel-app" role="tabpanel" aria-labelledby="two-factor-tab-app">
            <div class="mb-2.5 flex items-baseline justify-between gap-3">
                <span id="two-factor-otp-label" class="text-sm font-bold text-slate-700 dark:text-slate-200">Código de verificación</span>
                {{-- Los códigos TOTP cambian en cada periodo de 30 s del reloj. --}}
                <span class="inline-flex items-center gap-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400" aria-hidden="true">
                    <svg class="h-[18px] w-[18px] -rotate-90" viewBox="0 0 20 20"><circle cx="10" cy="10" r="8" fill="none" stroke-width="3" class="stroke-slate-200 dark:stroke-slate-700"/><circle data-two-factor-ring cx="10" cy="10" r="8" fill="none" stroke-width="3" stroke-linecap="round" stroke-dasharray="50.27" stroke-dashoffset="0" class="stroke-blue-500 transition-[stroke-dashoffset] duration-1000 ease-linear"/></svg>
                    <span data-two-factor-timer>Cambia en 30 s</span>
                </span>
            </div>
            <div class="grid grid-cols-6 gap-2 {{ $codeError && ! $locked ? 'ag-otp-error' : '' }}" data-two-factor-otp role="group" aria-labelledby="two-factor-otp-label">
                @foreach (range(1, 6) as $i)
                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="Dígito {{ $i }}" data-two-factor-digit @if ($i === 1) autocomplete="one-time-code" autofocus @else autocomplete="off" @endif @disabled($locked)
                        class="aspect-[1/1.15] max-h-16 w-full rounded-xl border-[1.5px] border-slate-200 bg-slate-50 p-0 text-center text-[26px] font-extrabold text-slate-900 caret-blue-500 transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/20 disabled:opacity-60 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50 dark:focus:bg-slate-900">
                @endforeach
            </div>
            <p class="mt-2.5 text-[13px] leading-5 text-slate-500 dark:text-slate-400">Puedes pegar el código completo. Al escribir el sexto dígito se verifica solo.</p>
        </div>

        <div class="mt-5" id="two-factor-panel-rec" role="tabpanel" aria-labelledby="two-factor-tab-rec" hidden>
            <label for="two-factor-recovery" class="mb-2.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Código de recuperación</label>
            <input id="two-factor-recovery" type="text" placeholder="XXXX-XXXX" maxlength="9" autocomplete="off" spellcheck="false" autocapitalize="characters" data-two-factor-recovery
                class="w-full rounded-xl border-[1.5px] border-slate-200 bg-slate-50 px-4 py-3.5 text-center font-mono text-xl font-bold uppercase tracking-[.14em] text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50 dark:focus:bg-slate-900">
            <p class="mt-2.5 text-[13px] leading-5 text-slate-500 dark:text-slate-400">Son los 8 códigos que guardaste al activar la verificación. Cada uno sirve <strong>una sola vez</strong>; después de entrar, genera códigos nuevos desde tu cuenta.</p>
        </div>

        @if ($locked)
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-3 text-sm leading-6 text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200" role="alert">
                <svg class="mt-0.5 h-[18px] w-[18px] flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <span><strong class="block">{{ $codeError }}</strong>Por seguridad, la verificación queda en pausa. Al terminar la espera también puedes usar un código de recuperación.</span>
            </div>
        @elseif ($codeError)
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-rose-300 bg-rose-50 px-3.5 py-3 text-sm leading-6 text-rose-800 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-200" role="alert" data-two-factor-error>
                <svg class="mt-0.5 h-[18px] w-[18px] flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01"/></svg>
                <span><strong class="block">{{ $codeError }}</strong>Revisa que la hora de tu teléfono sea automática y prueba con el código nuevo.@if (is_int($remaining)) {{ $remaining === 1 ? 'Te queda 1 intento.' : "Te quedan {$remaining} intentos." }}@endif</span>
            </div>
        @endif

        <button type="submit" data-two-factor-submit disabled
            class="mt-5 inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-blue-700 px-4 py-3.5 text-[15px] font-extrabold text-white shadow-lg shadow-blue-700/25 transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-55 disabled:shadow-none">
            <span class="hidden h-4 w-4 animate-spin rounded-full border-[2.5px] border-white/40 border-t-white" data-two-factor-spinner aria-hidden="true"></span>
            <span data-two-factor-submit-text>Verificar e ingresar</span>
        </button>
    </form>

    <details class="group mt-6 border-t border-slate-200 pt-4 dark:border-slate-700">
        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-slate-700 dark:text-slate-200 [&::-webkit-details-marker]:hidden">
            ¿Perdiste tu teléfono o cambiaste de aplicación?
            <span class="text-lg text-slate-500 group-open:hidden" aria-hidden="true">+</span><span class="hidden text-lg text-slate-500 group-open:inline" aria-hidden="true">–</span>
        </summary>
        <ol class="mt-3 list-decimal space-y-1.5 pl-[18px] text-sm leading-6 text-slate-600 dark:text-slate-300">
            <li>Usa uno de tus <button type="button" class="font-bold text-blue-800 hover:underline dark:text-sky-300" data-two-factor-tab="rec">códigos de recuperación</button>.</li>
            <li>Si no los tienes, escribe al equipo de Aprende Guate desde el correo de tu cuenta; verificaremos tu identidad antes de desactivar la verificación.</li>
            <li>Cuando recuperes el acceso, vuelve a registrar tu aplicación en <em>Seguridad y privacidad</em>.</li>
        </ol>
    </details>

    <p class="mt-4 flex gap-2.5 text-[12.5px] leading-5 text-slate-500 dark:text-slate-400">
        <svg class="mt-px h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        Te pedimos este paso porque protege tu progreso, tus certificados y tu perfil profesional aunque alguien conozca tu contraseña.
    </p>
</div>
<script nonce="{{ Vite::cspNonce() }}">
    (() => {
        const root = document.querySelector('[data-two-factor]');
        const form = root.querySelector('[data-two-factor-form]');
        const hidden = form.querySelector('input[name="code"]');
        const digits = [...root.querySelectorAll('[data-two-factor-digit]')];
        const otp = root.querySelector('[data-two-factor-otp]');
        const recovery = root.querySelector('[data-two-factor-recovery]');
        const submit = root.querySelector('[data-two-factor-submit]');
        const submitText = root.querySelector('[data-two-factor-submit-text]');
        const spinner = root.querySelector('[data-two-factor-spinner]');
        const panels = { app: root.querySelector('#two-factor-panel-app'), rec: root.querySelector('#two-factor-panel-rec') };
        const locked = @js($locked);
        let mode = 'app';
        let sending = false;

        const code = () => digits.map((d) => d.value).join('');
        const clearError = () => { otp.classList.remove('ag-otp-error'); root.querySelector('[data-two-factor-error]')?.remove(); };
        const refresh = () => {
            digits.forEach((d) => d.classList.toggle('!border-blue-700', d.value !== ''));
            const ready = mode === 'app' ? code().length === 6 : /^[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(recovery.value);
            submit.disabled = locked || sending || ! ready;
        };
        const send = () => { if (! submit.disabled) form.requestSubmit(); };

        // Casillas: solo números, avance automático, retroceso, flechas y pegar el código completo.
        digits.forEach((input, i) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(-1);
                clearError();
                if (input.value && i < 5) digits[i + 1].focus();
                refresh();
                if (code().length === 6) send();
            });
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && ! input.value && i > 0) { digits[i - 1].value = ''; digits[i - 1].focus(); refresh(); }
                if (event.key === 'ArrowLeft' && i > 0) digits[i - 1].focus();
                if (event.key === 'ArrowRight' && i < 5) digits[i + 1].focus();
            });
            input.addEventListener('paste', (event) => {
                const text = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6);
                if (! text) return;
                event.preventDefault();
                clearError();
                text.split('').forEach((c, k) => { digits[k].value = c; });
                digits[Math.min(text.length, 5)].focus();
                refresh();
                if (text.length === 6) send();
            });
            input.addEventListener('focus', () => input.select());
        });

        // Código de recuperación con guion automático: XXXX-XXXX.
        recovery.addEventListener('input', () => {
            const raw = recovery.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
            recovery.value = raw.length > 4 ? `${raw.slice(0, 4)}-${raw.slice(4)}` : raw;
            clearError();
            refresh();
        });

        const setMode = (next) => {
            mode = next;
            root.querySelectorAll('[role="tab"]').forEach((tab) => tab.setAttribute('aria-selected', String(tab.dataset.twoFactorTab === next)));
            Object.entries(panels).forEach(([key, panel]) => { panel.hidden = key !== next; });
            (next === 'app' ? digits[0] : recovery).focus();
            refresh();
        };
        root.querySelectorAll('[data-two-factor-tab]').forEach((el) => el.addEventListener('click', () => setMode(el.dataset.twoFactorTab)));

        form.addEventListener('submit', (event) => {
            if (submit.disabled) { event.preventDefault(); return; }
            hidden.value = mode === 'app' ? code() : recovery.value;
            sending = true;
            spinner.classList.remove('hidden');
            submitText.textContent = 'Verificando…';
            refresh();
        });

        const ring = root.querySelector('[data-two-factor-ring]');
        const timer = root.querySelector('[data-two-factor-timer]');
        const tick = () => {
            const left = 30 - (Math.floor(Date.now() / 1000) % 30);
            ring.style.strokeDashoffset = String(50.27 * (1 - left / 30));
            timer.textContent = `Cambia en ${left} s`;
        };
        tick();
        setInterval(tick, 1000);
        refresh();
    })();
</script>
