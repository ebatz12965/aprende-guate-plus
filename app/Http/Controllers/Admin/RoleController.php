<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/** Roles: los del sistema son fijos; los personalizados agrupan permisos delegables y solo los crea el super administrador. */
class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get()
            ->sortBy(fn (Role $role) => AdminAccess::sortKey($role->name))
            ->values();

        return view('admin.roles.index', ['roles' => $roles]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role, 'selected' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $role = new Role(['name' => $data['name'], 'guard_name' => 'web']);
        $role->save();
        $role->syncPermissions($data['permissions']);
        Audit::log('roles', 'Creó el rol «'.$role->name.'»', $role, ['permisos' => $this->labels($data['permissions'])]);

        return redirect()->route('admin.roles.index')->with('success', 'Rol creado. Ya puedes asignarlo desde la ficha de cada cuenta.');
    }

    public function edit(Role $role): View
    {
        abort_if(AdminAccess::isSystemRole($role->name), 404);

        return view('admin.roles.form', ['role' => $role, 'selected' => $role->permissions->pluck('name')->all()]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if(AdminAccess::isSystemRole($role->name), 404);
        $data = $this->validated($request, $role);
        $before = $role->permissions->pluck('name')->all();
        $oldName = $role->name;
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions']);

        Audit::log('roles', 'Editó el rol «'.$role->name.'»', $role, array_filter([
            'nombre' => $oldName !== $role->name ? ['antes' => $oldName, 'después' => $role->name] : null,
            'permisos agregados' => $this->labels(array_diff($data['permissions'], $before)) ?: null,
            'permisos retirados' => $this->labels(array_diff($before, $data['permissions'])) ?: null,
            'cuentas afectadas' => $role->users()->count() ?: null,
        ]));

        return redirect()->route('admin.roles.index')->with('success', 'Rol actualizado. El cambio aplica de inmediato a todas las cuentas que lo tienen.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(AdminAccess::isSystemRole($role->name), 404);
        $count = $role->users()->count();
        if ($count > 0) {
            return back()->with('error', "No se puede eliminar «{$role->name}»: lo tienen {$count} ".($count === 1 ? 'cuenta' : 'cuentas').'. Retíralo primero desde la ficha de cada una.');
        }
        Audit::log('roles', 'Eliminó el rol «'.$role->name.'»', null, ['permisos' => $this->labels($role->permissions->pluck('name')->all())]);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Rol eliminado.');
    }

    /** @return array{name: string, permissions: list<string>} */
    private function validated(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[\pL\pN ]+$/u',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id),
                Rule::notIn(AdminAccess::SYSTEM_ROLES)],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(AdminAccess::delegable())],
        ], [
            'name.regex' => 'Usa solo letras, números y espacios.',
            'name.not_in' => 'Ese nombre está reservado para un rol del sistema.',
            'permissions.required' => 'Elige al menos un permiso.',
        ]);

        return ['name' => trim($data['name']), 'permissions' => array_values(array_unique($data['permissions']))];
    }

    /** @param  array<int, string>  $permissions  @return list<string> */
    private function labels(array $permissions): array
    {
        return array_values(array_map(fn (string $p) => AdminAccess::DELEGABLE[$p][1] ?? $p, $permissions));
    }
}
