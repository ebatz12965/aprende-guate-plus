@extends('layouts.admin')

@section('content')
<div class="max-w-7xl">
    <a href="{{ route('admin.email-templates.index') }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">← Plantillas de correo</a>
    <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">{{ $definition['name'] }}</h1>
            <p class="mt-2 max-w-2xl text-slate-600">{{ $definition['description'] }}</p>
        </div>
        @if ($custom)
            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-800">Personalizada · {{ $custom->updated_at->format('d/m/Y H:i') }}{{ $custom->editor ? ' · '.$custom->editor->name : '' }}</span>
        @else
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">Usando el texto original</span>
        @endif
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <div class="space-y-6">
            <form id="template-form" method="POST" action="{{ route('admin.email-templates.update', $key) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf @method('PUT')
                <div class="space-y-5">
                    <label class="block text-sm font-bold text-slate-700">Asunto
                        <input name="subject" required maxlength="200" value="{{ old('subject', $fields['subject']) }}" class="template-field mt-2 block w-full rounded-xl border-slate-200">
                    </label>
                    <label class="block text-sm font-bold text-slate-700">Título del correo
                        <input name="heading" maxlength="200" value="{{ old('heading', $fields['heading']) }}" class="template-field mt-2 block w-full rounded-xl border-slate-200">
                    </label>
                    <label class="block text-sm font-bold text-slate-700">Mensaje
                        <textarea name="body" required maxlength="5000" rows="7" class="template-field mt-2 block w-full rounded-xl border-slate-200">{{ old('body', $fields['body']) }}</textarea>
                    </label>
                    @if ($key === 'mentorship_transcript')
                        <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">Debajo del mensaje, la plataforma agrega la conversación completa (quién escribió, la hora y cada mensaje). Esa parte no se edita.</p>
                    @endif
                    <label class="block text-sm font-bold text-slate-700">Texto del botón
                        <input name="action_label" maxlength="60" value="{{ old('action_label', $fields['action_label']) }}" class="template-field mt-2 block w-full rounded-xl border-slate-200">
                        <span class="mt-1 block text-xs font-normal text-slate-500">El enlace del botón lo pone la plataforma en cada envío.</span>
                    </label>
                    <label class="block text-sm font-bold text-slate-700">Cierre
                        <textarea name="closing" maxlength="2000" rows="4" class="template-field mt-2 block w-full rounded-xl border-slate-200">{{ old('closing', $fields['closing']) }}</textarea>
                    </label>
                </div>
                <p class="mt-4 text-xs leading-5 text-slate-500">Separa los párrafos con una línea en blanco. Puedes resaltar con <code class="rounded bg-slate-100 px-1">**negritas**</code> y agregar enlaces con <code class="rounded bg-slate-100 px-1">[texto](https://…)</code>.</p>
                <div class="mt-5 flex flex-wrap justify-end gap-3">
                    <button class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white hover:bg-blue-800">Guardar plantilla</button>
                </div>
            </form>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-900">Marcadores disponibles</h2>
                <p class="mt-1 text-sm text-slate-500">Haz clic en uno para insertarlo donde está el cursor.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($definition['placeholders'] as $name => $label)
                        <button type="button" data-placeholder="{{ '{'.$name.'}' }}" class="placeholder-chip rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm hover:border-blue-300 hover:bg-blue-50">
                            <code class="font-bold text-blue-800">{{ '{'.$name.'}' }}</code>
                            <span class="block text-xs text-slate-500">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-900">Enviar una prueba</h2>
                <p class="mt-1 text-sm text-slate-500">Usa la versión guardada y datos de ejemplo. Guarda tus cambios antes de probar.</p>
                <form method="POST" action="{{ route('admin.email-templates.test', $key) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                    @csrf
                    <input name="test_recipient" type="email" required class="w-full rounded-xl border-slate-200" placeholder="correo-destino@ejemplo.com">
                    <button class="shrink-0 rounded-xl border border-blue-600 px-5 py-3 text-sm font-bold text-blue-700 hover:bg-blue-50">Enviar prueba</button>
                </form>
            </section>

            @if ($custom)
                <form method="POST" action="{{ route('admin.email-templates.destroy', $key) }}" onsubmit="return confirm('¿Restaurar el texto original? Se perderán los cambios de esta plantilla.')" class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    @csrf @method('DELETE')
                    <p class="text-sm font-bold text-rose-900">Restaurar el texto original</p>
                    <p class="mt-1 text-sm text-rose-800">Descarta la versión personalizada y vuelve al texto con el que viene la plataforma.</p>
                    <button class="mt-3 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-700">Restaurar original</button>
                </form>
            @endif
        </div>

        <section class="xl:sticky xl:top-6 xl:self-start">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-slate-900">Vista previa</h2>
                <span id="preview-status" class="text-xs text-slate-500">Con datos de ejemplo</span>
            </div>
            <p id="preview-subject" class="mt-2 truncate rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700"></p>
            <iframe id="template-preview" title="Vista previa del correo" class="mt-3 h-[720px] w-full rounded-2xl border border-slate-200 bg-white" sandbox></iframe>
        </section>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('template-form');
        const frame = document.getElementById('template-preview');
        const status = document.getElementById('preview-status');
        const subject = document.getElementById('preview-subject');
        const samples = @json($definition['sample']);
        let lastField = form.querySelector('[name="body"]');
        let timer = null;

        const fillSubject = (text) => text.replace(/\{([a-z_]+)\}/g, (match, name) => samples[name] ?? match);

        const refresh = async () => {
            const data = new FormData(form);
            data.delete('_method');
            subject.textContent = 'Asunto: ' + fillSubject(data.get('subject') || '');
            status.textContent = 'Actualizando…';
            try {
                const response = await fetch(@json(route('admin.email-templates.preview', $key)), {method: 'POST', body: data, headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok) throw new Error();
                frame.srcdoc = await response.text();
                status.textContent = 'Con datos de ejemplo';
            } catch (error) {
                status.textContent = 'No se pudo actualizar la vista previa';
            }
        };

        form.querySelectorAll('.template-field').forEach((field) => {
            field.addEventListener('focus', () => { lastField = field; });
            field.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(refresh, 400); });
        });

        document.querySelectorAll('.placeholder-chip').forEach((chip) => chip.addEventListener('click', () => {
            const text = chip.dataset.placeholder;
            const start = lastField.selectionStart ?? lastField.value.length;
            const end = lastField.selectionEnd ?? start;
            lastField.value = lastField.value.slice(0, start) + text + lastField.value.slice(end);
            lastField.focus();
            lastField.setSelectionRange(start + text.length, start + text.length);
            refresh();
        }));

        refresh();
    })();
</script>
@endsection
