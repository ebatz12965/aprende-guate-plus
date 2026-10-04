<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('bio_visibility')->default('private')->after('bio');
            $table->json('education')->nullable()->after('bio_visibility');
            $table->string('education_visibility')->default('private')->after('education');
            $table->json('work_experience')->nullable()->after('education_visibility');
            $table->string('work_experience_visibility')->default('private')->after('work_experience');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'bio_visibility',
                'education',
                'education_visibility',
                'work_experience',
                'work_experience_visibility',
            ]);
        });
    }
};
