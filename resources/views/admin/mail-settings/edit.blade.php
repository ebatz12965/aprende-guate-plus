@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <p class="text-sm font-bold text-blue-700">CONFIGURACIÓN ADMINISTRATIVA</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Correo saliente</h1>
        <p class="mt-2 text-slate-600">Configura el servidor que envía verificaciones de correo y recuperación de contraseña. Usa una contraseña de aplicación, nunca la contraseña personal de la cuenta.</p>
    </div>

    <form action="{{ route('admin.mail-settings.update') }}" method="POST" class="space-y-6">
        @csrf @method('PUT')
        <section class="platform-feature">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled'])) class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span>
                    <span class="font-bold text-slate-900">Activar correo SMTP administrado</span>
                    <span class="mt-1 block text-sm text-slate-500">Al activarlo, estos valores reemplazan el correo configurado por archivo de entorno.</span>
                </span>
            </label>
        </section>

        <section class="platform-feature">
            <h2 class="!mt-0 text-xl">Servidor SMTP</h2>
            <p class="mt-1 text-sm text-slate-500">Los valores mostrados son los que están guardados actualmente. Guardar los cambios los aplica para los próximos envíos.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-bold text-slate-700 sm:col-span-2">Servidor / Host
                    <input name="host" value="{{ old('host', $settings['host']) }}" class="mt-2 block w-full rounded-xl border-slate-200" placeholder="smtp.tu-proveedor.com">
                </label>
                <label class="block text-sm font-bold text-slate-700">Puerto
                    <input id="smtp-port" name="port" type="number" min="1" max="65535" value="{{ old('port', $settings['port']) }}" class="mt-2 block w-full rounded-xl border-slate-200" placeholder="587">
                </label>
                <label class="block text-sm font-bold text-slate-700">Cifrado
                    <select id="smtp-encryption" name="encryption" class="mt-2 block w-full rounded-xl border-slate-200">
                        <option value="tls" @selected(old('encryption', $settings['encryption']) === 'tls')>TLS / STARTTLS (recomendado)</option>
                        <option value="ssl" @selected(old('encryption', $settings['encryption']) === 'ssl')>SSL / SMTPS</option>
                        <option value="none" @selected(old('encryption', $settings['encryption']) === 'none')>Sin cifrado</option>
                    </select>
                </label>
                <label class="block text-sm font-bold text-slate-700">Usuario SMTP
                    <input name="username" value="{{ old('username', $settings['username']) }}" autocomplete="off" class="mt-2 block w-full rounded-xl border-slate-200" placeholder="usuario o correo SMTP">
                </label>
                <label class="block text-sm font-bold text-slate-700">Contraseña de aplicación
                    <input type="password" name="password" autocomplete="new-password" class="mt-2 block w-full rounded-xl border-slate-200" placeholder="{{ $settings['has_password'] ? 'Contraseña guardada; escribe solo para cambiarla' : 'Contraseña o token SMTP' }}">
                    <span class="mt-1 block text-xs font-normal text-slate-500">{{ $settings['has_password'] ? 'Ya hay una contraseña cifrada. Déjalo vacío para conservarla.' : 'Se guarda cifrada y no se vuelve a mostrar.' }}</span>
                </label>
            </div>
            <div id="smtp-help" class="mt-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900"></div>
        </section>

        <section class="platform-feature">
            <h2 class="!mt-0 text-xl">Remitente</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-bold text-slate-700">Correo de salida
                    <input type="email" name="from_address" value="{{ old('from_address', $settings['from_address']) }}" class="mt-2 block w-full rounded-xl border-slate-200" placeholder="no-reply@tu-dominio.com">
                </label>
                <label class="block text-sm font-bold text-slate-700">Nombre visible
                    <input name="from_name" value="{{ old('from_name', $settings['from_name']) }}" class="mt-2 block w-full rounded-xl border-slate-200" placeholder="Aprende Guate">
                </label>
            </div>
        </section>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Para que los enlaces funcionen fuera de tu computadora, <strong>APP_URL</strong> debe apuntar a un dominio público con HTTPS. Guarda la configuración antes de probar registro o recuperación.</div>
        <div class="flex justify-end"><button class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white shadow-md shadow-blue-600/20 hover:bg-blue-700">Guardar correo saliente</button></div>
    </form>

    <section class="platform-feature mt-6">
        <h2 class="!mt-0 text-xl">Probar configuración</h2>
        <p class="mt-1 text-sm text-slate-500">Envía un mensaje real usando la configuración guardada. Guarda los cambios antes de probar. El asunto y el texto de la prueba se editan en <a href="{{ route('admin.email-templates.edit', 'mail_test') }}" class="font-bold text-blue-700 underline">Plantillas de correo</a>.</p>
        <form action="{{ route('admin.mail-settings.test') }}" method="POST" class="mt-5 flex flex-col gap-3 sm:flex-row">
            @csrf
            <input name="test_recipient" type="email" required class="w-full rounded-xl border-slate-200" placeholder="correo-destino@ejemplo.com">
            <button class="shrink-0 rounded-xl border border-blue-600 px-5 py-3 font-bold text-blue-700 hover:bg-blue-50">Enviar correo de prueba</button>
        </form>
    </section>
</div>

<script>
    (() => {
        const encryption = document.getElementById('smtp-encryption');
        const port = document.getElementById('smtp-port');
        const help = document.getElementById('smtp-help');
        const descriptions = {
            tls: ['TLS / STARTTLS', 'Usa SMTP con STARTTLS internamente. Puerto recomendado: 587.', '587'],
            ssl: ['SSL / SMTPS', 'Usa SMTPS internamente. Puerto recomendado: 465.', '465'],
            none: ['Sin cifrado', 'Solo úsalo si tu proveedor lo exige. Puerto habitual: 25.', '25'],
        };

        const updateHelp = () => {
            const [title, description, recommendedPort] = descriptions[encryption.value];
            help.innerHTML = '<strong>' + title + ':</strong> ' + description + ' <button type="button" class="ml-1 font-bold underline" id="use-recommended-port">Usar puerto ' + recommendedPort + '</button>';
            document.getElementById('use-recommended-port').addEventListener('click', () => { port.value = recommendedPort; });
        };

        encryption.addEventListener('change', updateHelp);
        updateHelp();
    })();
</script>
@endsection
