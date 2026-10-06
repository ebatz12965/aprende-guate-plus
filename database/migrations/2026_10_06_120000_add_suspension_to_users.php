<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Suspender una cuenta en lugar de eliminarla: suspended_until vacío significa suspensión permanente. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('two_factor_reminder_shown_at');
            $table->timestamp('suspended_until')->nullable()->after('suspended_at');
            $table->string('suspension_reason', 500)->nullable()->after('suspended_until');
            $table->foreignId('suspended_by')->nullable()->after('suspension_reason')->constrained('users')->nullOnDelete();
            $table->index('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['suspended_at', 'suspended_until', 'suspension_reason']);
        });
    }
};
