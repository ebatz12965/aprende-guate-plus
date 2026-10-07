<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Library\OfficePathBuilder;
use App\Support\Library\OfficeSyllabus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class LibraryOfficePath extends Command
{
    protected $signature = 'library:office-path {email : Cuenta que recibirá la ruta}
        {--from=principiante : Nivel inicial (principiante, intermedio, avanzado o experto)}
        {--to=experto : Nivel final}
        {--tools= : Herramientas separadas por coma (word,excel,powerpoint,outlook); vacío = todas}
        {--create : Crea la cuenta de estudiante si no existe}
        {--name=Estudiante de Office : Nombre de la cuenta nueva}';

    protected $description = 'Asigna una ruta de Microsoft Office armada con la biblioteca compartida (no consume tokens).';

    public function handle(OfficePathBuilder $builder): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();
        if (! $user && $this->option('create')) {
            $password = 'password';
            $user = User::create(['name' => (string) $this->option('name'), 'email' => $email, 'password' => Hash::make($password)]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole('Student');
            $this->info("Cuenta creada: {$email} / {$password}");
        }
        if (! $user) {
            $this->error('No existe esa cuenta. Usa --create para crearla.');

            return self::FAILURE;
        }

        $tools = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('tools')))));
        if ($unknown = array_diff($tools, array_keys(OfficeSyllabus::TOOLS))) {
            $this->error('Herramientas desconocidas: '.implode(', ', $unknown));

            return self::FAILURE;
        }

        try {
            $path = $builder->build($user, (string) $this->option('from'), (string) $this->option('to'), $tools);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $path->load('items.topics');
        $this->info("Ruta «{$path->title}»: {$path->items->count()} fases y {$path->items->sum(fn ($item) => $item->topics->count())} temas.");
        foreach ($path->items as $item) {
            $this->line(" {$item->position}. {$item->title} ({$item->topics->count()} temas)");
        }

        return self::SUCCESS;
    }
}
