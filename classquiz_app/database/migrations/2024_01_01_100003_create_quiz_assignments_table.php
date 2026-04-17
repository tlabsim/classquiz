<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('public_token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->string('title')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamp('registration_start')->nullable();
            $table->timestamp('registration_end')->nullable();
            $table->timestamp('availability_start')->nullable();
            $table->timestamp('availability_end')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_assignments');
    }
};
