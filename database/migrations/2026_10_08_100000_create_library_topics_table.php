<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biblioteca de contenido compartido (por ahora, Microsoft Office): cada tema se genera una sola vez
 * y se asigna a todas las rutas que lo necesitan, en lugar de generarlo por persona.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_topics', function (Blueprint $table) {
            $table->id();
            $table->string('course', 40);
            $table->string('slug', 160)->unique();
            $table->string('tool', 40);
            $table->string('level', 20);
            $table->unsignedSmallInteger('level_order');
            $table->unsignedSmallInteger('position');
            $table->string('title');
            $table->text('learning_objective');
            $table->string('practice_type', 40);
            $table->json('lesson')->nullable();
            $table->json('practice')->nullable();
            $table->json('walkthrough')->nullable();
            $table->timestamp('lesson_generated_at')->nullable();
            $table->timestamp('practice_generated_at')->nullable();
            $table->timestamp('walkthrough_generated_at')->nullable();
            $table->timestamps();
            $table->index(['course', 'level_order', 'tool', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_topics');
    }
};
