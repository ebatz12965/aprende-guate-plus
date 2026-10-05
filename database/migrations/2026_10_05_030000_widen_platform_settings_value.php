<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Algunas configuraciones (como los precios por modelo) son JSON y superan 255 caracteres.
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->text('value')->change();
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('value')->change();
        });
    }
};
