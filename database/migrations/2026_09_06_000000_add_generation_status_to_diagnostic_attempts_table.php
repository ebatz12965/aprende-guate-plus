<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_attempts', function (Blueprint $table) {
            $table->text('generation_error')->nullable()->after('status');
            $table->timestamp('generation_requested_at')->nullable()->after('generation_error');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_attempts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['generation_error', 'generation_requested_at']);
        });
    }
};
