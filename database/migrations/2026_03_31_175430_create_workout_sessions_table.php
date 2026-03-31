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
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session')->unique();
            $table->string('primary_muscle_group');
            $table->string('secondary_muscle_group');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sessions');
    }
};
