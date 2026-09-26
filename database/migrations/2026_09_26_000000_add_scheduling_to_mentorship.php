<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('session_duration_minutes')->default(30)->after('rejection_message');
            $table->json('availability')->nullable()->after('session_duration_minutes');
        });

        Schema::table('mentorship_sessions', function (Blueprint $table) {
            $table->dateTime('preferred_at')->nullable()->after('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('preferred_at');
            $table->foreignId('cancelled_by')->nullable()->after('decision_message')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->text('cancellation_message')->nullable()->after('cancelled_at');
            $table->json('reminders_sent')->nullable()->after('cancellation_message');
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('mentorship_sessions', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropColumn(['preferred_at', 'duration_minutes', 'cancelled_by', 'cancelled_at', 'cancellation_message', 'reminders_sent']);
        });

        Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->dropColumn(['session_duration_minutes', 'availability']);
        });
    }
};
