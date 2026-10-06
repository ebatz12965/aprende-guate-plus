<?php

use App\Support\AdminAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles del sistema y permisos delegables como datos: producción los recibe con migrate, sin seeders.
 * Los permisos exclusivos del super administrador no se guardan: no se pueden asignar a nadie más.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (AdminAccess::SYSTEM_ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
        foreach (AdminAccess::delegable() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', AdminAccess::delegable())->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
