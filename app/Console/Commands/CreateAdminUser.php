<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AdminAccess;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Crea (o restablece) la cuenta de súper administración con una contraseña temporal que se cambia al entrar. */
class CreateAdminUser extends Command
{
    protected $signature = 'users:create-admin {email : Correo de la cuenta} {--name= : Nombre (solo para una cuenta nueva)} {--reset : Si ya existe, le da una contraseña temporal nueva}';

    protected $description = 'Crea la cuenta de súper administración con una contraseña temporal (se muestra una sola vez y se cambia en el primer inicio).';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo no es válido.');

            return self::FAILURE;
        }
        $user = User::where('email', $email)->first();
        if ($user && ! $this->option('reset')) {
            $this->error('Esa cuenta ya existe. Usa --reset para darle una contraseña temporal nueva.');

            return self::FAILURE;
        }

        // Sin caracteres que se confunden al copiarla a mano (0/O, 1/l/I).
        $password = collect(str_split(Str::password(20, symbols: false)))->reject(fn ($c) => in_array($c, ['0', 'O', '1', 'l', 'I'], true))->take(12)->implode('').'-'.random_int(10, 99);
        $user ??= new User(['email' => $email, 'name' => (string) ($this->option('name') ?: Str::headline(Str::before($email, '@')))]);
        $user->forceFill(['password' => Hash::make($password), 'password_change_required' => true, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->assignRole([AdminAccess::SUPER_ROLE, AdminAccess::BASE_ROLE]);
        Audit::log('accounts', $user->wasRecentlyCreated ? 'Cuenta de súper administración creada desde la consola' : 'Contraseña temporal restablecida desde la consola', $user);

        $this->info(($user->wasRecentlyCreated ? 'Cuenta creada' : 'Contraseña restablecida').": {$email}");
        $this->line('Contraseña temporal (se muestra solo esta vez): '.$password);
        $this->line('Al iniciar sesión se pedirá cambiarla.');

        return self::SUCCESS;
    }
}
