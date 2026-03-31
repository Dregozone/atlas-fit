<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('workouts')->exists()) {
            return;
        }

        DB::table('workouts')->insert([
            // Chest
            ['session' => 'Chest',     'exercise_no' => 1, 'equipment' => 'Pec fly',                'weight_1rm' => 56.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Chest',     'exercise_no' => 2, 'equipment' => 'Chest press',             'weight_1rm' => 50.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Chest',     'exercise_no' => 3, 'equipment' => 'Close chest squeeze',     'weight_1rm' => 36.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Chest',     'exercise_no' => 4, 'equipment' => '(Ben.) Bench press',      'weight_1rm' => 134.2, 'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Chest',     'exercise_no' => 5, 'equipment' => 'Press ups',               'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
            // Shoulders
            ['session' => 'Shoulders', 'exercise_no' => 1, 'equipment' => 'Shoulder raises',         'weight_1rm' => 24.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Shoulders', 'exercise_no' => 2, 'equipment' => '(Ben.) Overhead press',   'weight_1rm' => 80.4,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Shoulders', 'exercise_no' => 3, 'equipment' => 'Chicken flaps',           'weight_1rm' => 19.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Shoulders', 'exercise_no' => 4, 'equipment' => 'Reverse fly',             'weight_1rm' => 30.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Shoulders', 'exercise_no' => 5, 'equipment' => 'Full arnold press',       'weight_1rm' => 19.0,  'created_at' => now(), 'updated_at' => now()],
            // Arms
            ['session' => 'Arms',      'exercise_no' => 1, 'equipment' => 'Bicep curls',             'weight_1rm' => 26.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Arms',      'exercise_no' => 2, 'equipment' => 'Tricep extensions',       'weight_1rm' => 32.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Arms',      'exercise_no' => 3, 'equipment' => 'Black ball tricep exten.', 'weight_1rm' => 28.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Arms',      'exercise_no' => 4, 'equipment' => 'Assisted chin ups',       'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Arms',      'exercise_no' => 5, 'equipment' => 'Assisted dips',           'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
            // Back
            ['session' => 'Back',      'exercise_no' => 1, 'equipment' => 'Seated row',              'weight_1rm' => 38.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Back',      'exercise_no' => 2, 'equipment' => 'Reverse fly',             'weight_1rm' => 30.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Back',      'exercise_no' => 3, 'equipment' => 'Lat pull down',           'weight_1rm' => 30.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Back',      'exercise_no' => 4, 'equipment' => 'Assisted pull ups',       'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Back',      'exercise_no' => 5, 'equipment' => 'Seated push downs',       'weight_1rm' => 45.0,  'created_at' => now(), 'updated_at' => now()],
            // Legs
            ['session' => 'Legs',      'exercise_no' => 1, 'equipment' => 'Leg extensions',          'weight_1rm' => 50.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Legs',      'exercise_no' => 2, 'equipment' => '(Ben.) Deadlift',         'weight_1rm' => 190.4, 'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Legs',      'exercise_no' => 3, 'equipment' => 'Leg press',               'weight_1rm' => 90.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Legs',      'exercise_no' => 4, 'equipment' => 'Calve press',             'weight_1rm' => 220.0, 'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Legs',      'exercise_no' => 5, 'equipment' => '(Ben.) Squat',            'weight_1rm' => 146.2, 'created_at' => now(), 'updated_at' => now()],
            // Abs
            ['session' => 'Abs',       'exercise_no' => 1, 'equipment' => 'Weighted ab crunches',    'weight_1rm' => 30.0,  'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Abs',       'exercise_no' => 2, 'equipment' => 'Leg raises',              'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Abs',       'exercise_no' => 3, 'equipment' => 'Plank',                   'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Abs',       'exercise_no' => 4, 'equipment' => 'Sit ups',                 'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
            ['session' => 'Abs',       'exercise_no' => 5, 'equipment' => 'Ab roller',               'weight_1rm' => 0.0,   'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('workouts')->whereIn('session', ['Chest', 'Shoulders', 'Arms', 'Back', 'Legs', 'Abs'])->delete();
    }
};
