<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('ai_token_usages', 'agent_slug')) {
            Schema::table('ai_token_usages', function (Blueprint $table) {
                $table->string('agent_slug', 80)->nullable()->after('operation');
                $table->string('provider', 80)->nullable()->after('agent_slug');
                $table->index(['agent_slug', 'created_at']);
                $table->index(['provider', 'created_at']);
            });
        }

        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id(); $table->string('slug')->unique(); $table->string('name'); $table->boolean('enabled')->default(false); $table->json('settings')->nullable(); $table->timestamps();
        });
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id(); $table->string('slug')->unique(); $table->string('name'); $table->text('description'); $table->string('provider', 80); $table->string('model', 160)->nullable(); $table->unsignedInteger('max_tokens')->default(800); $table->boolean('enabled')->default(true); $table->json('settings')->nullable(); $table->timestamps();
        });
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id(); $table->foreignId('ai_agent_id')->nullable()->constrained()->nullOnDelete(); $table->string('key'); $table->unsignedSmallInteger('version')->default(1); $table->longText('template'); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['key', 'version']); $table->index(['ai_agent_id', 'is_active']);
        });
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id(); $table->uuid('request_uuid')->unique(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('agent_slug', 80); $table->string('provider', 80); $table->string('model', 160); $table->string('operation', 80); $table->char('input_hash', 64); $table->char('output_hash', 64)->nullable(); $table->string('status', 32)->default('processing'); $table->unsignedInteger('input_tokens')->default(0); $table->unsignedInteger('output_tokens')->default(0); $table->unsignedInteger('total_tokens')->default(0); $table->unsignedInteger('duration_ms')->nullable(); $table->string('failure_reason', 500)->nullable(); $table->timestamp('completed_at')->nullable(); $table->timestamps(); $table->index(['agent_slug', 'created_at']); $table->index(['user_id', 'created_at']); $table->index(['status', 'created_at']);
        });
        Schema::create('ai_content_versions', function (Blueprint $table) {
            $table->id(); $table->string('content_type', 80); $table->unsignedBigInteger('content_id'); $table->unsignedSmallInteger('version'); $table->json('payload'); $table->foreignId('ai_request_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedTinyInteger('quality_score')->nullable(); $table->string('status', 32)->default('published'); $table->timestamps(); $table->unique(['content_type', 'content_id', 'version']); $table->index(['content_type', 'content_id']);
        });

        Schema::create('skill_categories', function (Blueprint $table) {
            $table->id(); $table->string('slug')->unique(); $table->string('name'); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->id(); $table->foreignId('skill_category_id')->nullable()->constrained()->nullOnDelete(); $table->string('slug')->unique(); $table->string('name'); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('skill_dependencies', function (Blueprint $table) {
            $table->id(); $table->foreignId('skill_id')->constrained()->cascadeOnDelete(); $table->foreignId('depends_on_skill_id')->constrained('skills')->cascadeOnDelete(); $table->timestamps(); $table->unique(['skill_id', 'depends_on_skill_id']);
        });
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('skill_id')->constrained()->cascadeOnDelete(); $table->unsignedTinyInteger('level')->default(0); $table->decimal('confidence', 5, 2)->default(0); $table->timestamp('last_evidenced_at')->nullable(); $table->timestamps(); $table->unique(['user_id', 'skill_id']); $table->index(['user_id', 'level']);
        });
        Schema::create('skill_evidences', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_skill_id')->constrained()->cascadeOnDelete(); $table->string('source_type', 80); $table->unsignedBigInteger('source_id')->nullable(); $table->unsignedTinyInteger('score')->nullable(); $table->text('summary')->nullable(); $table->json('metadata')->nullable(); $table->timestamps(); $table->index(['source_type', 'source_id']);
        });
        Schema::create('learning_path_versions', function (Blueprint $table) {
            $table->id(); $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete(); $table->foreignId('diagnostic_attempt_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedSmallInteger('version'); $table->json('architecture'); $table->string('generator', 80)->default('learning_path'); $table->timestamps(); $table->unique(['learning_path_id', 'version']);
        });
        Schema::create('learning_path_dependencies', function (Blueprint $table) {
            $table->id(); $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete(); $table->foreignId('learning_path_item_id')->constrained()->cascadeOnDelete(); $table->foreignId('depends_on_item_id')->constrained('learning_path_items')->cascadeOnDelete(); $table->timestamps(); $table->unique(['learning_path_item_id', 'depends_on_item_id'], 'lp_deps_item_dependency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_dependencies'); Schema::dropIfExists('learning_path_versions'); Schema::dropIfExists('skill_evidences'); Schema::dropIfExists('user_skills'); Schema::dropIfExists('skill_dependencies'); Schema::dropIfExists('skills'); Schema::dropIfExists('skill_categories'); Schema::dropIfExists('ai_content_versions'); Schema::dropIfExists('ai_requests'); Schema::dropIfExists('ai_prompts'); Schema::dropIfExists('ai_agents'); Schema::dropIfExists('ai_providers');
        Schema::table('ai_token_usages', function (Blueprint $table) { $table->dropIndex(['agent_slug', 'created_at']); $table->dropIndex(['provider', 'created_at']); $table->dropColumn(['agent_slug', 'provider']); });
    }
};