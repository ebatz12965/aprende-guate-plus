<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_path_topics', function (Blueprint $table) {
            $table->string('lesson_generation_status')->default('idle')->after('status');
            $table->string('practice_generation_status')->default('idle')->after('lesson_generation_status');
            $table->text('generation_error')->nullable()->after('practice_generation_status');
            $table->timestamp('generation_requested_at')->nullable()->after('generation_error');
            $table->index(['lesson_generation_status']);
            $table->index(['practice_generation_status']);
        });

        Schema::table('learning_paths', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'learning_paths_user_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropIndex('learning_paths_user_status_created_index');
        });

        Schema::table('learning_path_topics', function (Blueprint $table) {
            $table->dropIndex(['lesson_generation_status']);
            $table->dropIndex(['practice_generation_status']);
            $table->dropColumn(['lesson_generation_status', 'practice_generation_status', 'generation_error', 'generation_requested_at']);
        });
    }
};
