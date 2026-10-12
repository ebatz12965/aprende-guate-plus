<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Falso en cuentas creadas con Google: su contraseña es aleatoria y nadie la conoce.
            $table->boolean('has_local_password')->default(true)->after('password');
            // Último periodo TOTP aceptado: impide usar el mismo código dos veces.
            $table->unsignedBigInteger('two_factor_last_counter')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_local_password', 'two_factor_last_counter']);
        });
    }
};
