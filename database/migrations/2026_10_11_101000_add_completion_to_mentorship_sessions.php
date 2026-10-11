<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentorship_sessions', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('cancellation_message');
            // Nulo cuando la plataforma la cerró sola al pasar el tiempo de la sesión.
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('transcript_sent_at')->nullable()->after('completed_by');
        });
    }

    public function down(): void
    {
        Schema::table('mentorship_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn(['completed_at', 'transcript_sent_at']);
        });
    }
};
