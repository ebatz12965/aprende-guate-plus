<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_path_item_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('selected_answer');
            $table->boolean('is_correct');
            $table->timestamp('attempted_at');
            $table->timestamps();
            $table->index(['learning_path_item_id', 'user_id'], 'lpia_item_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_item_quiz_attempts');
    }
};
