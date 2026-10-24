<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Contraseña «password» y rol de administración: nunca fuera de local ni de pruebas, aunque se llame con --class.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('UserSeeder crea cuentas con contraseña conocida y solo se ejecuta en local o en pruebas.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
        $admin->assignRole('Admin');

        $instructor = User::updateOrCreate(
            ['email' => 'instructor@example.com'],
            [
                'name' => 'Instructor User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
        $instructor->assignRole('Instructor');

        $student = User::updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
        $student->assignRole('Student');
    }
}
