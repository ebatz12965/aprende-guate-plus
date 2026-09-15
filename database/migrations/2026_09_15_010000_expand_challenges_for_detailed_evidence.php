<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->longText('case_context')->nullable()->after('description');
            $table->json('objectives')->nullable()->after('case_context');
            $table->json('deliverables')->nullable()->after('objectives');
            $table->json('requirements')->nullable()->after('deliverables');
            $table->json('rubric')->nullable()->after('requirements');
            $table->json('resources')->nullable()->after('rubric');
            $table->unsignedSmallInteger('estimated_minutes')->nullable()->after('resources');
        });

        Schema::table('challenge_submissions', function (Blueprint $table) {
            $table->longText('methodology')->nullable()->after('answer');
            $table->string('attachment_path')->nullable()->after('methodology');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            $table->longText('attachment_analysis')->nullable()->after('attachment_size');
            $table->json('evaluation')->nullable()->after('feedback');
            $table->timestamp('evaluated_at')->nullable()->after('evaluation');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_submissions', function (Blueprint $table) {
            $table->dropColumn(['methodology', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size', 'attachment_analysis', 'evaluation', 'evaluated_at']);
        });
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn(['case_context', 'objectives', 'deliverables', 'requirements', 'rubric', 'resources', 'estimated_minutes']);
        });
    }
};