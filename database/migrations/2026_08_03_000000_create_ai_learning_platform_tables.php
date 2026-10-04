<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('professional_goal')->nullable();
            $table->string('current_level')->default('Inicial');
            $table->string('linkedin_url')->nullable();
            $table->string('cv_path')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnostic_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('level')->default('Inicial');
            $table->json('answers');
            $table->json('skill_results')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('goal')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('learning_path_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('area')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('area')->nullable();
            $table->string('level')->default('Inicial');
            $table->text('description');
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('challenge_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('answer')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamps();
            $table->unique(['challenge_id', 'user_id']);
        });

        Schema::create('mentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->string('specialties')->nullable();
            $table->text('about')->nullable();
            $table->timestamps();
        });

        Schema::create('mentorship_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('topic');
            $table->string('status')->default('requested');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Tutor IA');
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('mentorship_sessions');
        Schema::dropIfExists('mentor_profiles');
        Schema::dropIfExists('challenge_submissions');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('learning_path_items');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('diagnostic_attempts');
        Schema::dropIfExists('student_profiles');
    }
};
