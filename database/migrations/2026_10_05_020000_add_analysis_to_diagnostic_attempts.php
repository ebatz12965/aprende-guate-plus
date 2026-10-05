<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_attempts', function (Blueprint $table) {
            // Análisis de la meta (agente goal_analyst): base del cuestionario y de la ruta.
            $table->json('analysis')->nullable()->after('primary_answer');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_attempts', function (Blueprint $table) {
            $table->dropColumn('analysis');
        });
    }
};
