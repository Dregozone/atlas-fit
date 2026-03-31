<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workout_sessions')->insertOrIgnore([
            ['session' => 'a', 'primary_muscle_group' => 'Chest', 'secondary_muscle_group' => 'Shoulders', 'created_at' => now(), 'updated_at' => now()],
            ['session' => 'b', 'primary_muscle_group' => 'Arms',  'secondary_muscle_group' => 'Back',      'created_at' => now(), 'updated_at' => now()],
            ['session' => 'c', 'primary_muscle_group' => 'Legs',  'secondary_muscle_group' => 'Abs',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('workout_sessions')->whereIn('session', ['a', 'b', 'c'])->delete();
    }
};
