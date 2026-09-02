<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->string('estimated_level')->nullable()->after('goal');
            $table->unsignedSmallInteger('estimated_hours')->nullable()->after('estimated_level');
            $table->string('generation_version')->nullable()->after('estimated_hours');
        });

        Schema::create('learning_path_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_item_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('learning_objective');
            $table->string('practice_type');
            $table->unsignedSmallInteger('position');
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['learning_path_item_id', 'position']);
        });

        Schema::create('learning_path_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_topic_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('content');
            $table->json('resources')->nullable();
            $table->json('quiz')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });

        Schema::create('learning_path_practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_topic_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->json('instructions');
            $table->json('artifact')->nullable();
            $table->json('expected_solution')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });

        Schema::create('learning_path_practice_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_path_practice_id');
            $table->foreign('learning_path_practice_id', 'lp_practice_attempt_fk')->references('id')->on('learning_path_practices')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id', 'lp_practice_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->longText('answer')->nullable();
            $table->string('status')->default('submitted');
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_practice_attempts');
        Schema::dropIfExists('learning_path_practices');
        Schema::dropIfExists('learning_path_lessons');
        Schema::dropIfExists('learning_path_topics');
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropColumn(['estimated_level', 'estimated_hours', 'generation_version']);
        });
    }
};
