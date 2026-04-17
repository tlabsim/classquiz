<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 26); // ULID length
            $table->foreign('session_id')->references('id')->on('quiz_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->json('selected_choice_ids')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 6, 2)->nullable();
            $table->boolean('is_manually_graded')->default(false);
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('saved_at')->nullable();

            $table->unique(['session_id', 'question_id']); // upsert target
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
