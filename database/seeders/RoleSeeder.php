<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::findOrCreate('Admin', 'web');
        Role::findOrCreate('Instructor', 'web');
        Role::findOrCreate('Student', 'web');
    }
}
