<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
            $table->foreignId('learning_path_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('milestone')->nullable()->after('learning_path_id');
            $table->string('generation_status')->default('completed')->after('milestone');
            $table->timestamp('generated_at')->nullable()->after('generation_status');
            $table->unique(['user_id', 'learning_path_id', 'milestone'], 'challenge_user_path_milestone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropUnique('challenge_user_path_milestone_unique');
            $table->dropConstrainedForeignId('learning_path_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['milestone', 'generation_status', 'generated_at']);
        });
    }
};
