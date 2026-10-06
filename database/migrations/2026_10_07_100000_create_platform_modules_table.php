<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Estado de cada módulo de la plataforma; sin fila, el módulo usa el estado por defecto de App\Support\Modules. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('status', 20);
            $table->string('message', 500)->nullable();
            $table->timestamp('until')->nullable();
            $table->json('preview_roles')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_modules');
    }
};
