<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->string('source_video_path')->nullable()->after('video_url');
            $table->string('hls_path')->nullable()->after('source_video_path');
            $table->string('video_status')->nullable()->after('hls_path');
            $table->text('video_error')->nullable()->after('video_status');
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropColumn(['source_video_path', 'hls_path', 'video_status', 'video_error']);
        });
    }
};
