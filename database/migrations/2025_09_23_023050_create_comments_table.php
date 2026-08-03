<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');

            // Para respuestas anidadas (un comentario puede pertenecer a otro comentario)
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');

            // Columnas para la relación polimórfica (un comentario puede pertenecer a un curso, una clase, etc.)
            $table->morphs('commentable');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
