<footer class="border-t border-slate-200/80 bg-white/80 text-slate-600 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
    <div class="mx-auto grid w-full max-w-[1600px] gap-7 px-4 py-8 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div>
            <img src="{{ asset('images/aprende-guate-logo.png') }}" alt="Aprende Guate" class="h-14 w-auto max-w-[9rem] object-contain">
            <p class="mt-3 max-w-xs text-xs leading-5">Aprendizaje práctico y personalizado para desarrollar tus competencias.</p>
            <p class="mt-3 text-[11px]">© {{ date('Y') }} Edwing Batz · Todos los derechos reservados</p>
        </div>
        <nav class="text-xs font-semibold" aria-label="Información legal">
            <p class="mb-3 text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Información</p>
            <div class="grid gap-2">
                <a href="{{ route('legal.terms') }}" class="transition hover:text-blue-700 dark:hover:text-blue-300">Términos y condiciones</a>
                <a href="{{ route('legal.privacy') }}" class="transition hover:text-blue-700 dark:hover:text-blue-300">Política de privacidad</a>
                <a href="{{ route('legal.cookies') }}" class="transition hover:text-blue-700 dark:hover:text-blue-300">Política de cookies</a>
                <a href="{{ route('legal.faq') }}" class="transition hover:text-blue-700 dark:hover:text-blue-300">Preguntas frecuentes</a>
            </div>
        </nav>
        <div class="text-xs">
            <p class="mb-3 text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Contacto y soporte</p>
            <div class="grid gap-2">
                <a href="mailto:ebatzm@miumg.edu.gt" class="font-semibold transition hover:text-blue-700 dark:hover:text-blue-300">ebatzm@miumg.edu.gt</a>
                <a href="tel:+50240718171" class="font-semibold transition hover:text-blue-700 dark:hover:text-blue-300">+502 4071 8171</a>
                <span class="text-slate-500 dark:text-slate-400">Atención por correo y teléfono</span>
            </div>
        </div>
        <div class="text-xs font-bold" aria-label="Redes sociales">
            <p class="mb-3 text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Síguenos</p>
            <div class="flex items-center gap-2">
            <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="grid h-7 w-7 place-items-center rounded-full border border-slate-200 transition hover:border-blue-500 hover:text-blue-700 dark:border-slate-700 dark:hover:border-blue-400 dark:hover:text-blue-300" aria-label="Facebook">f</a>
            <a href="https://x.com/" target="_blank" rel="noopener noreferrer" class="grid h-7 w-7 place-items-center rounded-full border border-slate-200 transition hover:border-blue-500 hover:text-blue-700 dark:border-slate-700 dark:hover:border-blue-400 dark:hover:text-blue-300" aria-label="X">𝕏</a>
            <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" class="grid h-7 w-7 place-items-center rounded-full border border-slate-200 transition hover:border-blue-500 hover:text-blue-700 dark:border-slate-700 dark:hover:border-blue-400 dark:hover:text-blue-300" aria-label="Instagram">◎</a>
            <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" class="grid h-7 w-7 place-items-center rounded-full border border-slate-200 transition hover:border-blue-500 hover:text-blue-700 dark:border-slate-700 dark:hover:border-blue-400 dark:hover:text-blue-300" aria-label="YouTube">▶</a>
            </div>
        </div>
    </div>
</footer>
