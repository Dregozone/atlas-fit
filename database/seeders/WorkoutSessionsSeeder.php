<?php

namespace Database\Seeders;

use App\Models\WorkoutSession;
use Illuminate\Database\Seeder;

class WorkoutSessionsSeeder extends Seeder
{
    public function run(): void
    {
        WorkoutSession::truncate();

        WorkoutSession::insert([
            ['session' => 'a', 'primary_muscle_group' => 'Chest', 'secondary_muscle_group' => 'Shoulders', 'created_at' => now(), 'updated_at' => now()],
            ['session' => 'b', 'primary_muscle_group' => 'Arms',  'secondary_muscle_group' => 'Back',      'created_at' => now(), 'updated_at' => now()],
            ['session' => 'c', 'primary_muscle_group' => 'Legs',  'secondary_muscle_group' => 'Abs',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
