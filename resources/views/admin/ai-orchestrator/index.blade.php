@extends('layouts.admin')
@section('content')
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="admin-eyebrow">ARQUITECTURA DE IA</p><h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-950">Orquestador de IA</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Coordina agentes especializados, conserva trazabilidad sin guardar prompts ni respuestas en texto plano y mantiene los proveedores externos separados de la lógica educativa.</p></div><a href="{{ route('admin.ai-usage.index') }}" class="admin-secondary-button">Ver consumo detallado</a></div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><div class="admin-stat-card"><p>Proveedor actual</p><strong>OpenAI</strong><span class="{{ $providerConfigured && $providerEnabled ? 'text-emerald-700' : 'text-amber-700' }}">{{ $providerConfigured && $providerEnabled ? 'Configurado y disponible' : 'Pendiente de credencial o activación' }}</span></div><div class="admin-stat-card"><p>Agentes activos</p><strong>{{ $agents->where('enabled', true)->count() }}</strong><span>de {{ $agents->count() }} registrados</span></div><div class="admin-stat-card"><p>Solicitudes trazadas</p><strong>{{ number_format($totalRequests) }}</strong><span>{{ number_format($totalTokens) }} tokens registrados</span></div><div class="admin-stat-card"><p>Solicitudes con fallo</p><strong>{{ number_format($failedRequests) }}</strong><span>Revisa el historial inferior</span></div></div>
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
            <p class="mt-2 text-xs font-medium text-slate-500">{{ $agent['provider'] }} · {{ $agent['model'] }} · {{ $agent['max_tokens_override'] ? 'máximo '.number_format($agent['max_tokens_override']).' tokens' : 'límite de tokens según cada operación' }}</p>
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
            <label class="block text-sm font-bold text-slate-700">Modelo<input name="model" maxlength="160" value="{{ old('agent') === $agent['slug'] ? old('model') : ($agent['model'] === $agent['default_model'] ? '' : $agent['model']) }}" placeholder="{{ $agent['default_model'] }}" class="mt-1 block w-full rounded-xl border-slate-200 text-sm"><span class="mt-1 block text-xs font-normal text-slate-500">Vacío: {{ $agent['default_model'] }}</span></label>
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