<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->text('acceptance_message')->nullable()->after('about');
            $table->text('rejection_message')->nullable()->after('acceptance_message');
        });

        Schema::table('mentorship_sessions', function (Blueprint $table) {
            $table->timestamp('responded_at')->nullable()->after('notes');
            $table->text('decision_message')->nullable()->after('responded_at');
        });

        Schema::create('mentorship_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentorship_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_automatic')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('in_app_messages')->default(true);
            $table->boolean('email_messages')->default(true);
            $table->boolean('in_app_mentorship')->default(true);
            $table->boolean('email_mentorship')->default(true);
            $table->boolean('in_app_platform')->default(true);
            $table->boolean('email_platform')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->string('audience')->default('all');
            $table->boolean('send_email')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('mentorship_messages');

        Schema::table('mentorship_sessions', function (Blueprint $table) {
            $table->dropColumn(['responded_at', 'decision_message']);
        });

        Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->dropColumn(['acceptance_message', 'rejection_message']);
        });
    }
};