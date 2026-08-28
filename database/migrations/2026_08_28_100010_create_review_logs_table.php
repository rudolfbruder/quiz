<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('response_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('rating');
            $table->float('stability_before')->nullable();
            $table->float('difficulty_before')->nullable();
            $table->float('stability_after');
            $table->float('difficulty_after');
            $table->float('elapsed_days')->nullable();
            $table->float('scheduled_days');
            $table->unsignedInteger('answered_in_milliseconds')->nullable();
            $table->timestamp('reviewed_at');

            $table->index(['user_id', 'question_id', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
