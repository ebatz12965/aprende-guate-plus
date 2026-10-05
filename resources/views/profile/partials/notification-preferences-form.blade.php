<section>
    <header>
        <h2 class="text-xl font-extrabold text-slate-900">Notificaciones</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Elige cómo deseas recibir mensajes, decisiones de mentoría y avisos de la plataforma.</p>
    </header>

    <form method="POST" action="{{ route('notifications.preferences.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PATCH')
        @foreach(['messages' => 'Mensajes de mentoría', 'mentorship' => 'Solicitudes y decisiones', 'platform' => 'Avisos de la plataforma'] as $type => $label)
            <fieldset class="border-t border-slate-100 pt-5">
                <legend class="text-sm font-extrabold text-slate-800">{{ $label }}</legend>
                <p class="mt-1 text-xs leading-5 text-slate-500">Controla los canales que se usarán para este tipo de información.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50/50">
                        <input type="checkbox" name="in_app_{{ $type }}" value="1" @checked($notificationPreferences->{'in_app_'.$type}) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        En la aplicación
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50/50">
                        <input type="checkbox" name="email_{{ $type }}" value="1" @checked($notificationPreferences->{'email_'.$type}) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Por correo electrónico
                    </label>
                </div>
            </fieldset>
        @endforeach
        <button class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-extrabold text-white hover:bg-blue-800">Guardar preferencias</button>
    </form>
</section>