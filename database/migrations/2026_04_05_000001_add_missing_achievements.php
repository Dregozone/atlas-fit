<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $missing = [
            ['name' => 'Bench press 135lb (1 plate)',  'img' => 'benchPress.jpg',   'details' => '1RM 61kg/135lb',  'satisfied_by_item' => '(Ben.) Bench press', 'satisfied_by_amount' => 135.0],
            ['name' => 'Squat 135lb (1 plate)',        'img' => 'squat.jpg',         'details' => '1RM 61kg/135lb',  'satisfied_by_item' => '(Ben.) Squat',       'satisfied_by_amount' => 135.0],
            ['name' => 'Deadlift 135lb (1 plate)',     'img' => 'deadlift.jpg',      'details' => '1RM 61kg/135lb',  'satisfied_by_item' => '(Ben.) Deadlift',    'satisfied_by_amount' => 135.0],
        ];

        foreach ($missing as $achievement) {
            DB::table('achievements')
                ->where('name', $achievement['name'])
                ->exists() || DB::table('achievements')->insert(array_merge($achievement, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
        }
    }

    public function down(): void
    {
        DB::table('achievements')->whereIn('name', [
            'Bench press 135lb (1 plate)',
            'Squat 135lb (1 plate)',
            'Deadlift 135lb (1 plate)',
        ])->delete();
    }
};
