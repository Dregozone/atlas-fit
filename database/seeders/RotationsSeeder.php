<?php

namespace Database\Seeders;

use App\Models\Rotation;
use Illuminate\Database\Seeder;

class RotationsSeeder extends Seeder
{
    public function run(): void
    {
        Rotation::truncate();

        Rotation::insert([
            ['week' => 1, 'program' => 'Heavy',       'sets' => 5, 'reps' => 5,  'weight_percent' => 80, 'created_at' => now(), 'updated_at' => now()],
            ['week' => 2, 'program' => 'Volume',      'sets' => 3, 'reps' => 12, 'weight_percent' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['week' => 3, 'program' => 'Contraction', 'sets' => 4, 'reps' => 8,  'weight_percent' => 65, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
