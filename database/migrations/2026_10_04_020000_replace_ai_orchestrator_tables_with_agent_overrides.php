<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_agents, ai_prompts y ai_providers solo reflejaban config/ai.php y nadie los leía.
 * ai_agents se recrea para guardar únicamente los cambios hechos desde administración:
 * cada columna null significa "usar el valor de config/ai.php".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_providers');
        Schema::dropIfExists('ai_agents');

        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->boolean('enabled')->nullable();
            $table->string('model', 160)->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agents');

        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('provider', 80);
            $table->string('model', 160)->nullable();
            $table->unsignedInteger('max_tokens')->default(800);
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->unsignedSmallInteger('version')->default(1);
            $table->longText('template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['key', 'version']);
            $table->index(['ai_agent_id', 'is_active']);
        });
    }
};
