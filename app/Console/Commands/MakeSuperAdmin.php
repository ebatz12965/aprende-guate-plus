<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AdminAccess;
use App\Support\Audit;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/** Recuperación desde el servidor: da (o quita) el rol de super administrador y levanta una suspensión si la hay. */
class MakeSuperAdmin extends Command
{
    protected $signature = 'admin:super {email : Correo de la cuenta} {--quitar : Retira el rol en lugar de darlo}';

    protected $description = 'Asigna o retira el rol de super administrador desde la consola';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No existe una cuenta con ese correo.');

            return self::FAILURE;
        }

        if ($this->option('quitar')) {
            if (User::role(AdminAccess::SUPER_ROLE)->notSuspended()->whereKeyNot($user->id)->doesntExist()) {
                $this->error('Debe quedar al menos un super administrador activo.');

                return self::FAILURE;
            }
            $user->removeRole(AdminAccess::SUPER_ROLE);
            Audit::log('roles', 'Retiró el rol de super administrador desde la consola', $user);
            $this->info("{$user->email} ya no es super administrador.");

            return self::SUCCESS;
        }

        $user->assignRole(Role::findOrCreate(AdminAccess::SUPER_ROLE, 'web'));
        $user->assignRole(Role::findOrCreate(AdminAccess::BASE_ROLE, 'web'));
        $user->forceFill(['suspended_at' => null, 'suspended_until' => null, 'suspension_reason' => null, 'suspended_by' => null])->save();
        Audit::log('roles', 'Asignó el rol de super administrador desde la consola', $user);
        $this->info("{$user->email} ahora es super administrador.");

        return self::SUCCESS;
    }
}
