@extends('layouts.admin')
@section('content')
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><h1 class="text-3xl font-extrabold tracking-tight text-slate-950">Orquestador de IA</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Coordina agentes especializados, conserva trazabilidad sin guardar prompts ni respuestas en texto plano y mantiene los proveedores externos separados de la lógica educativa.</p></div><a href="{{ route('admin.ai-usage.index') }}" class="admin-secondary-button">Ver consumo detallado</a></div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><div class="admin-stat-card"><p>Proveedores con API key</p><strong>{{ collect($providers)->where('configured', true)->count() }}</strong><span>de {{ count($providers) }} disponibles</span></div><div class="admin-stat-card"><p>Agentes activos</p><strong>{{ $agents->where('enabled', true)->count() }}</strong><span>de {{ $agents->count() }} registrados</span></div><div class="admin-stat-card"><p>Solicitudes trazadas</p><strong>{{ number_format($totalRequests) }}</strong><span>{{ number_format($totalTokens) }} tokens registrados</span></div><div class="admin-stat-card"><p>Solicitudes con fallo</p><strong>{{ number_format($failedRequests) }}</strong><span>Revisa el historial inferior</span></div></div>
<section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div><h2 class="font-bold text-slate-950">Proveedores de IA</h2><p class="mt-1 text-sm text-slate-600">Un proveedor se activa al agregar su API key en el archivo .env. La lista de modelos se consulta a cada proveedor.</p></div>
        <form method="POST" action="{{ route('admin.ai-orchestrator.models.refresh') }}">@csrf<button class="admin-secondary-button">Actualizar lista de modelos</button></form>
    </div>
    <div class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-y-0 xl:grid-cols-4">
        @foreach ($providers as $slug => $provider)
            <div class="px-5 py-4 sm:px-6">
                <p class="font-bold text-slate-900">{{ $provider['name'] }}</p>
                @if ($provider['configured'])
                    <p class="mt-1 text-sm font-semibold text-emerald-700">Configurado · {{ count($models[$slug] ?? []) }} modelos</p>
                @else
                    <p class="mt-1 text-sm text-slate-600">Falta <code class="rounded bg-slate-100 px-1 text-xs">{{ $provider['key_env'] }}</code> en .env</p>
                    @if ($provider['keys_url'])<a href="{{ $provider['keys_url'] }}" target="_blank" rel="noopener" class="mt-1 inline-flex text-sm font-bold text-blue-700 underline-offset-4 hover:underline">Obtener API key</a>@endif
                @endif
            </div>
        @endforeach
    </div>
</section>
<section id="precios" class="mt-8 scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-950">Precios por modelo</h2><p class="mt-1 text-sm text-slate-600">Dólares por millón de tokens, según la página de precios de cada proveedor. El tablero de IA los usa para estimar el costo; un modelo sin precio no suma costo.</p></div>
    <form method="POST" action="{{ route('admin.ai-orchestrator.prices') }}" class="px-5 py-4 sm:px-6">@csrf @method('PUT')
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs font-bold text-slate-600"><tr><th class="py-2 pr-4">Modelo</th><th class="py-2 pr-4">Entrada (US$ / 1 M)</th><th class="py-2">Salida (US$ / 1 M)</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($priceModels as $index => $model)
                        <tr>
                            <td class="py-2 pr-4 font-semibold text-slate-900">{{ $model }}<input type="hidden" name="prices[{{ $index }}][model]" value="{{ $model }}"></td>
                            <td class="py-2 pr-4"><label class="sr-only" for="price-in-{{ $index }}">Precio de entrada de {{ $model }}</label><input id="price-in-{{ $index }}" type="number" step="0.0001" min="0" max="1000" name="prices[{{ $index }}][input]" value="{{ $prices[$model]['input'] ?? '' }}" placeholder="0.00" class="w-32 rounded-xl border-slate-200 text-sm tabular-nums"></td>
                            <td class="py-2"><label class="sr-only" for="price-out-{{ $index }}">Precio de salida de {{ $model }}</label><input id="price-out-{{ $index }}" type="number" step="0.0001" min="0" max="1000" name="prices[{{ $index }}][output]" value="{{ $prices[$model]['output'] ?? '' }}" placeholder="0.00" class="w-32 rounded-xl border-slate-200 text-sm tabular-nums"></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-slate-600">Aún no hay modelos en uso.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($priceModels)<button class="mt-4 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Guardar precios</button>@endif
    </form>
</section>
<div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-950">Agentes especializados</h2><p class="mt-1 text-sm text-slate-500">Cada agente tiene alcance, límite de respuesta y telemetría independientes. Puedes activar o desactivar cada agente y ajustar su modelo o límite de tokens; los que aún no tienen un módulo de producto no se pueden activar.</p></div><div class="divide-y divide-slate-100">@foreach ($agents as $agent)
<div class="px-5 py-5 sm:px-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-bold text-slate-900">{{ $agent['name'] }}</h3>
                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $agent['enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $agent['planned'] ? 'Sin módulo de producto' : ($agent['enabled'] ? 'Activo' : 'Desactivado') }}</span>
                @if ($agent['customized'])<span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Personalizado</span>@endif
            </div>
            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $agent['description'] }}</p>
            <p class="mt-2 text-xs font-medium text-slate-500">{{ $providers[$agent['provider']]['name'] ?? $agent['provider'] }} · {{ $agent['model'] }} · {{ ['reasoning' => 'tarea de razonamiento', 'writing' => 'tarea de redacción', 'fast' => 'tarea rápida'][$agent['task']] ?? '' }} · {{ $agent['max_tokens_override'] ? 'máximo '.number_format($agent['max_tokens_override']).' tokens' : 'límite de tokens según cada operación' }}</p>
        </div>
        <div class="grid grid-cols-3 gap-5 text-right text-sm">
            <div><p class="text-slate-500">Solicitudes</p><strong class="text-slate-900">{{ number_format($agent['requests']) }}</strong></div>
            <div><p class="text-slate-500">Correctas</p><strong class="text-slate-900">{{ number_format($agent['completed']) }}</strong></div>
            <div><p class="text-slate-500">Tokens</p><strong class="text-slate-900">{{ number_format($agent['tokens']) }}</strong></div>
        </div>
    </div>
    @unless ($agent['planned'])
    <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4" @if ($errors->any() && old('agent') === $agent['slug']) open @endif>
        <summary class="cursor-pointer text-sm font-bold text-slate-700">Configurar</summary>
        <form method="POST" action="{{ route('admin.ai-orchestrator.update', $agent['slug']) }}" class="mt-4 grid gap-4 sm:grid-cols-3">
            @csrf @method('PUT')
            <input type="hidden" name="agent" value="{{ $agent['slug'] }}">
            <label class="flex items-center gap-2 text-sm font-bold text-slate-700 sm:col-span-3"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" class="rounded border-slate-300 text-blue-600" @checked(old('agent') === $agent['slug'] ? old('enabled') : $agent['enabled'])> Agente activo <span class="font-normal text-slate-500">— desactivado, la plataforma usa el contenido de respaldo</span></label>
            @php($currentProvider = old('agent') === $agent['slug'] ? (old('provider') ?: $agent['provider']) : $agent['provider'])
            @php($currentModel = old('agent') === $agent['slug'] ? old('model') : ($agent['model'] === $agent['default_model'] && $agent['provider'] === $agent['default_provider'] ? '' : $agent['model']))
            <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2" x-data="{ provider: @js($currentProvider), model: @js($currentModel), models: @js($models), custom: false,
                    init() { this.custom = this.model !== '' && ! (this.models[this.provider] || []).includes(this.model); } }">
                <label class="block text-sm font-bold text-slate-700">Proveedor
                    <select name="provider" x-model="provider" @change="model = ''; custom = ! (models[provider] || []).length" class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                        @foreach ($providers as $slug => $provider)
                            <option value="{{ $slug }}" @disabled(! $provider['configured'])>{{ $provider['name'] }}{{ $provider['configured'] ? '' : ' (sin API key)' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-bold text-slate-700">Modelo
                    <select x-show="! custom" :name="custom ? '' : 'model'" x-model="model" @change="if (model === '__custom') { model = ''; custom = true }" class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                        <option value="" x-show="provider === @js($agent['default_provider'])">Predeterminado ({{ $agent['default_model'] }})</option>
                        <option value="" x-show="provider !== @js($agent['default_provider'])" disabled>Elige un modelo</option>
                        <template x-for="item in (models[provider] || [])" :key="item"><option :value="item" x-text="item" :selected="item === model"></option></template>
                        <option value="__custom">Escribir otro modelo…</option>
                    </select>
                    <input x-show="custom" x-cloak :name="custom ? 'model' : ''" x-model="model" maxlength="160" placeholder="Identificador exacto del modelo" class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                    <button type="button" x-show="custom && (models[provider] || []).length" x-cloak @click="custom = false; model = ''" class="mt-1 text-xs font-bold text-blue-700 underline-offset-4 hover:underline">Elegir de la lista</button>
                </label>
            </div>
            <label class="block text-sm font-bold text-slate-700">Máximo de tokens<input type="number" name="max_tokens" min="50" max="8000" value="{{ old('agent') === $agent['slug'] ? old('max_tokens') : $agent['max_tokens_override'] }}" placeholder="Automático" class="mt-1 block w-full rounded-xl border-slate-200 text-sm"><span class="mt-1 block text-xs font-normal text-slate-500">Vacío: cada operación usa su propio límite.</span></label>
            <div class="flex items-end gap-2">
                <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Guardar</button>
            </div>
            @if ($errors->any() && old('agent') === $agent['slug'])<div class="text-sm text-rose-700 sm:col-span-3">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        </form>
        @if ($agent['customized'])
        <form method="POST" action="{{ route('admin.ai-orchestrator.reset', $agent['slug']) }}" class="mt-3">@csrf @method('DELETE')<button class="text-xs font-bold text-slate-600 underline hover:text-slate-900">Restablecer valores de config/ai.php</button></form>
        @endif
    </details>
    @endunless
</div>
@endforeach</div></div>
<div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-950">Últimas solicitudes</h2><p class="mt-1 text-sm text-slate-500">Solo se guardan metadatos, hashes y consumo; nunca mensajes ni respuestas completas.</p></div><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Agente</th><th class="px-5 py-3">Usuario</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Tokens</th><th class="px-5 py-3 text-right">Duración</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($recentRequests as $request)<tr><td class="px-5 py-3 text-slate-600">{{ $request->created_at->format('d/m H:i') }}</td><td class="px-5 py-3 font-medium text-slate-900">{{ $request->agent_slug }}</td><td class="px-5 py-3 text-slate-600">{{ $request->user?->email ?? 'Sistema' }}</td><td class="px-5 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $request->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($request->status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ $request->status }}</span></td><td class="px-5 py-3 text-right text-slate-700">{{ number_format($request->total_tokens) }}</td><td class="px-5 py-3 text-right text-slate-700">{{ $request->duration_ms ? number_format($request->duration_ms).' ms' : '—' }}</td></tr>@empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aún no hay solicitudes trazadas. Genera un diagnóstico, contenido o consulta al tutor para ver actividad aquí.</td></tr>@endforelse</tbody></table></div></div>
@endsection