@extends('layouts.admin')

@section('content')
@use('App\Support\AdminAccess')
@use('App\Support\Modules')
@php
    $chip = fn (string $status) => match ($status) {
        Modules::MAINTENANCE => 'bg-amber-50 text-amber-800 ring-amber-200',
        Modules::PREVIEW => 'bg-violet-50 text-violet-800 ring-violet-200',
        default => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
    };
    $field = 'mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
@endphp
<div class="max-w-5xl">
    <div class="mb-7">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Módulos</h1>
        <p class="mt-2 text-slate-600">Pon un módulo en mantenimiento para que nadie lo use mientras lo arreglas, o en vista previa para probarlo antes de abrirlo. Lo que ya estaba en proceso (por ejemplo, una lección generándose) termina normalmente.</p>
    </div>

    <dl class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><dt class="font-bold text-slate-900">Activo</dt><dd class="mt-1 text-sm text-slate-600">Todas las personas lo usan.</dd></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><dt class="font-bold text-slate-900">En mantenimiento</dt><dd class="mt-1 text-sm text-slate-600">Sigue en el menú con una etiqueta; al entrar se ve tu aviso. Solo tú puedes usarlo.</dd></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><dt class="font-bold text-slate-900">Vista previa</dt><dd class="mt-1 text-sm text-slate-600">Solo lo ven los roles que elijas y el super administrador. Para los demás no existe.</dd></div>
    </dl>

    <div class="space-y-4">
        @foreach ($modules as $module)
            @php
                $state = $module['state'];
                $current = old('module') === $module['key'] ? old('status') : ($state['expired'] ? Modules::ACTIVE : $state['status']);
                $dependsOff = collect($module['depends'] ?? [])->filter(fn ($dep) => $modules[$dep]['state']['status'] !== Modules::ACTIVE);
            @endphp
            <section id="modulo-{{ $module['key'] }}" class="scroll-mt-28 rounded-2xl border bg-white p-5 shadow-sm sm:p-6 {{ $state['status'] === Modules::ACTIVE ? 'border-slate-200' : 'border-amber-200' }}"
                x-data="{ status: '{{ $current }}' }">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-extrabold text-slate-900">{{ $module['label'] }}</h2>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $chip($state['status']) }}">{{ Modules::STATUSES[$state['status']] }}</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $module['description'] }}</p>
                        @if (! empty($module['note']))<p class="mt-1 text-sm leading-6 text-slate-500">{{ $module['note'] }}</p>@endif
                        @if ($state['status'] === Modules::MAINTENANCE && $state['until'])
                            <p class="mt-2 text-sm font-semibold text-amber-800">Se reactiva solo el {{ $state['until']->format('d/m/Y') }} a las {{ $state['until']->format('H:i') }}.</p>
                        @elseif ($state['expired'])
                            <p class="mt-2 text-sm font-semibold text-emerald-800">El mantenimiento terminó el {{ $state['until']->format('d/m/Y H:i') }}; ya está activo.</p>
                        @endif
                        @if ($state['status'] === Modules::PREVIEW)
                            <p class="mt-2 text-sm text-violet-800">Lo ven: super administrador{{ $state['roles'] ? ', '.collect($state['roles'])->map(AdminAccess::roleLabel(...))->implode(', ') : '' }}.</p>
                        @endif
                        @if ($dependsOff->isNotEmpty())
                            <p class="mt-2 text-sm text-amber-800">Depende de {{ $dependsOff->map(fn ($dep) => '«'.$modules[$dep]['label'].'»')->implode(', ') }}, que no está activo: no se crearán contenidos nuevos aunque este siga abierto.</p>
                        @endif
                    </div>
                    @if ($module['row']?->editor)
                        <p class="shrink-0 text-xs text-slate-500">Último cambio: {{ $module['row']->updated_at->format('d/m/Y H:i') }} · {{ $module['row']->editor->name }}</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.modules.update', $module['key']) }}" class="mt-5 space-y-4 border-t border-slate-100 pt-5">
                    @csrf @method('PUT')
                    <input type="hidden" name="module" value="{{ $module['key'] }}">
                    <fieldset>
                        <legend class="sr-only">Estado de {{ $module['label'] }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (Modules::STATUSES as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="{{ $value }}" x-model="status" class="peer sr-only">
                                    <span class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 peer-checked:border-blue-400 peer-checked:bg-blue-50 peer-checked:text-blue-900 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div x-show="status === 'maintenance'" x-cloak class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_16rem]">
                        <div>
                            <label for="message-{{ $module['key'] }}" class="block text-sm font-semibold text-slate-700">Aviso para las personas <span class="font-normal text-slate-500">(opcional)</span></label>
                            <textarea id="message-{{ $module['key'] }}" name="message" rows="2" maxlength="500" class="{{ $field }}" placeholder="Estamos mejorando {{ Str::lower($module['label']) }}. Vuelve en un rato.">{{ old('module') === $module['key'] ? old('message') : $state['message'] }}</textarea>
                        </div>
                        <div>
                            <label for="until-{{ $module['key'] }}" class="block text-sm font-semibold text-slate-700">Reactivar solo <span class="font-normal text-slate-500">(opcional)</span></label>
                            <input id="until-{{ $module['key'] }}" type="datetime-local" name="until" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                                value="{{ old('module') === $module['key'] ? old('until') : ($state['status'] === Modules::MAINTENANCE ? $state['until']?->format('Y-m-d\TH:i') : '') }}" class="{{ $field }}">
                        </div>
                    </div>

                    <fieldset x-show="status === 'preview'" x-cloak>
                        <legend class="text-sm font-semibold text-slate-700">Además del super administrador, lo ven:</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($roles as $role)
                                <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:border-blue-300">
                                    <input type="checkbox" name="preview_roles[]" value="{{ $role }}" class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500" @checked(in_array($role, $state['roles'], true))>
                                    {{ AdminAccess::roleLabel($role) }}
                                </label>
                            @empty
                                <p class="text-sm text-slate-500">No hay otros roles.</p>
                            @endforelse
                        </div>
                    </fieldset>

                    @if (old('module') === $module['key'])
                        @foreach (['status', 'message', 'until', 'preview_roles'] as $errorKey)
                            @error($errorKey)<p class="text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                        @endforeach
                    @endif

                    <div class="flex justify-end">
                        <button class="inline-flex min-h-11 items-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Guardar</button>
                    </div>
                </form>
            </section>
        @endforeach
    </div>
</div>
@endsection
