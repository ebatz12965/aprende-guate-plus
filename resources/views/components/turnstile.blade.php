@props(['action'])
{{-- Verificación de Cloudflare Turnstile. Se dibuja cuando el formulario se vuelve visible (la ventana de inicio de sesión empieza cerrada). --}}
@if (\App\Support\Turnstile::enabled())
    <div>
        <div data-turnstile data-sitekey="{{ \App\Support\Turnstile::siteKey() }}" data-action="{{ $action }}" class="min-h-[65px]"></div>
        <x-input-error :messages="$errors->get('cf-turnstile-response')" class="mt-2" />
    </div>
    @once
        <script nonce="{{ Vite::cspNonce() }}">
            window.agTurnstileLoad = () => {
                const render = (el) => {
                    if (el.dataset.widgetId !== undefined) return;
                    el.dataset.widgetId = window.turnstile.render(el, {
                        sitekey: el.dataset.sitekey,
                        action: el.dataset.action,
                        language: 'es',
                        theme: 'auto',
                        size: 'flexible',
                        // Cada token sirve una sola vez y vence a los 5 minutos: se renueva solo.
                        'refresh-expired': 'auto',
                    });
                };
                const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
                    if (entry.isIntersecting) { render(entry.target); observer.unobserve(entry.target); }
                }));
                document.querySelectorAll('[data-turnstile]').forEach((el) => observer.observe(el));
            };
        </script>
        <script nonce="{{ Vite::cspNonce() }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=agTurnstileLoad" async defer></script>
    @endonce
@endif
