<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('diagnostic_attempts', function (Blueprint $table) {
            $table->text('primary_answer')->nullable()->after('user_id');
            $table->json('questions')->nullable()->after('answers');
            $table->string('status')->default('started')->after('level');
        });
        Schema::table('learning_path_items', function (Blueprint $table) {
            $table->json('content')->nullable()->after('description');
            $table->json('quiz')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_items', function (Blueprint $table) { $table->dropColumn(['content', 'quiz']); });
        Schema::table('diagnostic_attempts', function (Blueprint $table) { $table->dropColumn(['primary_answer', 'questions', 'status']); });
    }
};
