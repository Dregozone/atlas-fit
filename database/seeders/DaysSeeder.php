<?php

namespace Database\Seeders;

use App\Models\Day;
use Illuminate\Database\Seeder;

class DaysSeeder extends Seeder
{
    public function run(): void
    {
        Day::truncate();

        Day::insert([
            ['day' => 'Monday',    'session' => 'a',  'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Tuesday',   'session' => null, 'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Wednesday', 'session' => 'b',  'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Thursday',  'session' => null, 'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Friday',    'session' => 'c',  'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Saturday',  'session' => null, 'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Sunday',    'session' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
