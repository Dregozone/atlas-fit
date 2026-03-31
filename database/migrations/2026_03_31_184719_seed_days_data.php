<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('days')->insertOrIgnore([
            ['day' => 'Monday',    'session' => 'a',  'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Tuesday',   'session' => null, 'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Wednesday', 'session' => 'b',  'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Thursday',  'session' => null, 'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Friday',    'session' => 'c',  'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Saturday',  'session' => null, 'created_at' => now(), 'updated_at' => now()],
            ['day' => 'Sunday',    'session' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('days')->whereIn('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->delete();
    }
};
