<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mensajes de las entrevistas simuladas: lo que dice el entrevistador ficticio y lo que responde la persona. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_stage_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            // interviewer o candidate
            $table->string('role', 20);
            $table->text('content');
            // kind (opening, question, follow_up, closing, answer) y el número de pregunta.
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['interview_stage_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_turns');
    }
};
