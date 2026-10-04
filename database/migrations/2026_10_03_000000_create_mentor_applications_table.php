<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending_review')->index();
            $table->string('professional_title', 160);
            $table->string('specialties', 500);
            $table->unsignedSmallInteger('years_experience');
            $table->text('professional_background');
            $table->text('teaching_approach');
            $table->json('availability')->nullable();
            $table->unsignedSmallInteger('session_duration_minutes')->default(30);
            $table->text('motivation');
            $table->json('questionnaire_answers');
            $table->json('ai_evaluation')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('test_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_applications');
    }
};
