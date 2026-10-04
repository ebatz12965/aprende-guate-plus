<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_path_practice_attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->nullable()->after('status');
            $table->json('evaluation')->nullable()->after('feedback');
            $table->timestamp('evaluated_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_practice_attempts', function (Blueprint $table) {
            $table->dropColumn(['score', 'evaluation', 'evaluated_at']);
        });
    }
};
