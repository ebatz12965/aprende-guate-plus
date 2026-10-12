{{--
    Cierre por inactividad en el navegador. La actividad (teclado, clic, scroll, toque) se comparte entre pestañas
    con localStorage; si la persona lee sin hacer peticiones, se avisa al servidor cada pocos minutos.
    Dos minutos antes del límite aparece "¿Sigues ahí?". En una prueba con tiempo, la sesión no se cierra antes de que termine.
--}}
@auth
    <div x-data="idleWatch({ limit: {{ \App\Http\Middleware\EnforceIdleTimeout::limitFor(auth()->user()) * 60 }}, warn: {{ (int) config('idle.warning_seconds') }}, ping: @js(route('session.ping')), login: @js(route('login')), holdUntil: @js($idleHoldUntil ?? null) })">
        <div x-show="warning" x-cloak class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/50 p-4" role="alertdialog" aria-modal="true" aria-labelledby="idle-title" aria-describedby="idle-text"
            @keydown.escape.window="if (warning) keepAlive()">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <h2 id="idle-title" class="text-xl font-extrabold text-slate-900 dark:text-white">¿Sigues ahí?</h2>
                <p id="idle-text" class="mt-2 leading-7 text-slate-600 dark:text-slate-300">Por seguridad cerraremos tu sesión en <span class="font-bold tabular-nums" x-text="countdown"></span> si no hay actividad. Lo que ya guardaste no se pierde.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" x-ref="stay" @click="keepAlive()" class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white hover:bg-blue-800">Seguir conectado</button>
                    <button type="button" @click="expire()" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:text-slate-200">Cerrar sesión ahora</button>
                </div>
            </div>
        </div>
        <form x-ref="expireForm" method="POST" action="{{ route('session.expire') }}" class="hidden">@csrf</form>
    </div>
    <script nonce="{{ Vite::cspNonce() }}">
        function idleWatch(config) {
            const KEY = 'ag-last-activity';
            return {
                ...config, warning: false, countdown: '', lastPing: Date.now(), local: Date.now(), expiring: false,
                init() {
                    this.mark();
                    let lastEvent = 0;
                    const onActivity = () => {
                        const now = Date.now();
                        // Mientras se muestra el aviso, solo cuenta pulsar "Seguir conectado".
                        if (this.warning || now - lastEvent < 5000) return;
                        lastEvent = now;
                        this.mark();
                    };
                    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart', 'wheel'].forEach((name) => window.addEventListener(name, onActivity, { passive: true }));
                    window.setInterval(() => this.tick(), 1000);
                },
                mark() {
                    this.local = Date.now();
                    try { localStorage.setItem(KEY, String(this.local)); } catch (e) {}
                },
                last() {
                    let shared = 0;
                    try { shared = Number(localStorage.getItem(KEY) || 0); } catch (e) {}
                    return Math.max(shared, this.local);
                },
                tick() {
                    if (this.expiring) return;
                    // Prueba con tiempo en curso: cuenta como actividad hasta su hora límite.
                    if (this.holdUntil && new Date(this.holdUntil).getTime() > Date.now()) this.local = Date.now();
                    const idle = (Date.now() - this.last()) / 1000;
                    if (idle >= this.limit) return this.expire();
                    // Hubo actividad sin peticiones (por ejemplo, leyendo): se avisa al servidor cada 4 minutos.
                    if (this.last() > this.lastPing && Date.now() - this.lastPing > 240000) this.sendPing();
                    const left = this.limit - idle;
                    const wasWarning = this.warning;
                    this.warning = left <= this.warn;
                    if (this.warning) {
                        const seconds = Math.ceil(left);
                        this.countdown = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
                        // Se enfoca cuando el aviso ya es visible: así basta con Enter para seguir.
                        if (!wasWarning) window.setTimeout(() => this.$refs.stay?.focus(), 50);
                    }
                },
                async sendPing() {
                    this.lastPing = Date.now();
                    try {
                        const response = await fetch(this.ping, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } });
                        if (response.status === 401 || response.status === 419) window.location.href = this.login;
                    } catch (e) {}
                },
                keepAlive() {
                    this.warning = false;
                    this.mark();
                    this.sendPing();
                },
                expire() {
                    if (this.expiring) return;
                    this.expiring = true;
                    this.$refs.expireForm.submit();
                },
            };
        }
    </script>
@endauth
