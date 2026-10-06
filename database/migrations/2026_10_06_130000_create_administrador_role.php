<?php

use App\Support\AdminAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rol "Administrador": un nivel debajo del super administrador. Empieza con todos los permisos delegables
 * y el super administrador los ajusta en Administración → Roles. Nunca incluye los exclusivos
 * (eliminar cuentas, roles, límites de IA, orquestador y correo).
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($role) {
            return;
        }
        $role = Role::create(['name' => 'Administrador', 'guard_name' => 'web']);
        $role->syncPermissions(AdminAccess::delegable());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($role && $role->users()->doesntExist()) {
            $role->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
