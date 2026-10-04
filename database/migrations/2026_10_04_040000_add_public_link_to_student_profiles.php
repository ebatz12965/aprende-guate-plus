<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * El perfil público se servía en /perfil/{id}, enumerable para cualquier visitante.
 * Ahora usa un enlace no adivinable y solo se muestra si la persona lo activa (privado por defecto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('public_slug', 40)->nullable()->unique()->after('user_id');
            $table->boolean('is_public')->default(false)->after('public_slug');
        });

        DB::table('student_profiles')->whereNull('public_slug')->orderBy('id')->each(function ($profile) {
            DB::table('student_profiles')->where('id', $profile->id)->update(['public_slug' => Str::random(24)]);
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropUnique(['public_slug']);
            $table->dropColumn(['public_slug', 'is_public']);
        });
    }
};
