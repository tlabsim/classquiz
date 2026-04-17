<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('assignment_id')->constrained('quiz_assignments')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('class_id', 100)->nullable();
            $table->string('access_code')->nullable();          // bcrypt hash
            $table->timestamp('access_code_expires_at')->nullable();
            $table->string('resume_token')->nullable(); // bcrypt hash
            $table->enum('status', ['pending', 'active', 'in_progress', 'submitted', 'graded'])
                  ->default('pending');
            $table->json('question_order')->nullable();
            $table->json('quiz_snapshot')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'email']);
            $table->index(['assignment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
