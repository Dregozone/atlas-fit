<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each user's own changes to the shared programme: exercises they have switched off,
     * and weekdays where they train a different session (or rest) from the programme.
     */
    public function up(): void
    {
        Schema::create('excluded_workouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'workout_id']);
        });

        Schema::create('user_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('day', 20);
            $table->string('session', 50)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_days');
        Schema::dropIfExists('excluded_workouts');
    }
};
