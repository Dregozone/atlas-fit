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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->float('body_weight_lbs')->nullable()->after('username');
            $table->string('fitness_goal')->nullable()->after('body_weight_lbs');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'body_weight_lbs', 'fitness_goal']);
        });
    }
};
