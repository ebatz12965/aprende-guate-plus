@extends('layouts.admin')

@section('content')
<div class="max-w-5xl">
    <p class="text-sm font-bold text-blue-700">CONFIGURACIÓN ADMINISTRATIVA</p>
    <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Plantillas de correo</h1>
    <p class="mt-2 max-w-2xl text-slate-600">Edita el asunto y el texto de cada correo que envía la plataforma. El diseño (logo, colores y pie) se mantiene igual en todos; los datos de cada envío se colocan con marcadores como <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm text-slate-800">{nombre}</code>.</p>

    <div class="mt-7 grid gap-4 md:grid-cols-2">
        @foreach ($definitions as $key => $definition)
            @php($row = $custom->get($key))
            <a href="{{ route('admin.email-templates.edit', $key) }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="font-extrabold text-slate-900 group-hover:text-blue-800">{{ $definition['name'] }}</h2>
                    @if ($row)
                        <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-800">Personalizada</span>
                    @else
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Texto original</span>
                    @endif
                </div>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $definition['description'] }}</p>
                <p class="mt-4 truncate text-sm text-slate-500"><span class="font-bold text-slate-700">Asunto:</span> {{ $row?->subject ?? $definition['subject'] }}</p>
                @if ($row)
                    <p class="mt-1 text-xs text-slate-500">Editada {{ $row->updated_at->diffForHumans() }}{{ $row->editor ? ' por '.$row->editor->name : '' }}</p>
                @endif
            </a>
        @endforeach
    </div>

    <p class="mt-6 text-sm text-slate-500">¿Necesitas configurar el servidor que envía los correos? Ve a <a href="{{ route('admin.mail-settings.edit') }}" class="font-bold text-blue-700 underline">Correo saliente</a>.</p>
</div>
@endsection
