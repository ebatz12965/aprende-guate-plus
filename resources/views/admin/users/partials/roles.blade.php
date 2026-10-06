{{-- Casillas de roles. $assignable: rol => ¿lo puede asignar quien edita? · $checked: roles marcados · $locked: deshabilitar todo --}}
@use('App\Support\AdminAccess')
@php
    $models = \Spatie\Permission\Models\Role::with('permissions')->whereIn('name', array_keys($assignable))->get()->keyBy('name');
    $locked ??= false;
    $legend ??= true;
@endphp
<fieldset>
    <legend class="{{ $legend ? 'text-sm font-semibold text-slate-700' : 'sr-only' }}">Roles</legend>
    <p class="{{ $legend ? 'mt-1' : '' }} text-sm text-slate-500">Una cuenta puede tener varios. Estudiante es la base de todas.</p>
    <div class="mt-3 grid gap-2">
        @foreach ($assignable as $role => $canAssign)
            @php
                $isBase = $role === AdminAccess::BASE_ROLE;
                $disabled = $locked || $isBase || ! $canAssign;
                $permissions = $models[$role]?->permissions->pluck('name')->all() ?? [];
                $description = AdminAccess::roleDescription($role)
                    ?? ($permissions ? collect($permissions)->map(fn ($p) => AdminAccess::DELEGABLE[$p][1] ?? $p)->implode(' · ') : 'Sin permisos de administración.');
            @endphp
            <label class="flex items-start gap-3 rounded-xl border p-3 {{ $disabled ? 'border-slate-100 bg-slate-50' : 'cursor-pointer border-slate-200 hover:border-blue-300' }}">
                <input type="checkbox" name="roles[]" value="{{ $role }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500"
                    @checked($isBase || in_array($role, $checked, true)) @disabled($disabled)>
                @if ($disabled && in_array($role, $checked, true) && ! $isBase)
                    {{-- Un rol que no se puede tocar se conserva tal cual al guardar. --}}
                    <input type="hidden" name="roles[]" value="{{ $role }}">
                @endif
                <span class="min-w-0">
                    <span class="block font-bold text-slate-900">{{ AdminAccess::roleLabel($role) }}</span>
                    <span class="block text-sm leading-6 text-slate-600">{{ $description }}</span>
                    @if (! $canAssign && ! $isBase)
                        <span class="mt-1 block text-xs font-semibold text-slate-500">Solo un super administrador puede asignarlo o retirarlo.</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
</fieldset>
