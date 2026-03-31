<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('rotations')->exists()) {
            return;
        }

        DB::table('rotations')->insert([
            ['week' => 1, 'program' => 'Heavy',       'sets' => '5', 'reps' => '5',  'weight_percent' => 80, 'created_at' => now(), 'updated_at' => now()],
            ['week' => 2, 'program' => 'Volume',      'sets' => '3', 'reps' => '12', 'weight_percent' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['week' => 3, 'program' => 'Contraction', 'sets' => '4', 'reps' => '8',  'weight_percent' => 65, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('rotations')->whereIn('week', [1, 2, 3])->delete();
    }
};
