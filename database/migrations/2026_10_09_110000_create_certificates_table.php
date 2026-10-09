<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificados de rutas completadas. Guardan una copia de los datos impresos (nombre, ruta, firma) para que
 * el certificado no cambie si después se edita el perfil o la ruta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_path_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('title');
            $table->string('goal', 500)->nullable();
            $table->string('level', 40)->nullable();
            $table->unsignedSmallInteger('hours')->nullable();
            $table->unsignedSmallInteger('phases')->default(0);
            $table->unsignedSmallInteger('topics')->default(0);
            $table->string('signer_name', 120);
            $table->string('signer_title', 160);
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->index(['user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
