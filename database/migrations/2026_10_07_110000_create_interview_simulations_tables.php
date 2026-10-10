<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Simulador de postulación: un proceso de selección ficticio por puesto, con sus etapas. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('job_title', 160);
            $table->string('area', 40);
            $table->string('seniority', 30);
            $table->text('job_description')->nullable();
            $table->json('company')->nullable();
            $table->text('summary')->nullable();
            // generating → ready → in_progress → completed; failed si no se pudo generar el plan.
            $table->string('status', 20)->default('generating');
            $table->string('generated_by', 20)->nullable();
            $table->text('generation_error')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->json('report')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('interview_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_simulation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            // hr_interview, reasoning_test, technical_interview o practical_test.
            $table->string('type', 30);
            $table->string('title', 200);
            $table->text('purpose');
            $table->json('interviewer')->nullable();
            $table->json('focus')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->json('content')->nullable();
            $table->json('answers')->nullable();
            // locked → available → in_progress → submitted → evaluated
            $table->string('status', 20)->default('locked');
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('evaluation')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
            $table->unique(['interview_simulation_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_stages');
        Schema::dropIfExists('interview_simulations');
    }
};
