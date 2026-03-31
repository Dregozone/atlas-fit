<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('achievements')->exists()) {
            return;
        }

        DB::table('achievements')->insert([
            ['name' => 'Bench press 225lb (2 plates)',    'img' => 'benchPress.jpg',   'details' => '1RM 102kg/225lb', 'satisfied_by_item' => '(Ben.) Bench press',    'satisfied_by_amount' => 225.0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Overhead press 135lb (1 plate)', 'img' => 'overheadPress.jpg', 'details' => '1RM 61kg/135lb',  'satisfied_by_item' => '(Ben.) Overhead press', 'satisfied_by_amount' => 135.0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Squat 225lb (2 plates)',         'img' => 'squat.jpg',         'details' => '1RM 102kg/225lb', 'satisfied_by_item' => '(Ben.) Squat',          'satisfied_by_amount' => 225.0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Squat 315lb (3 plates)',         'img' => 'squat.jpg',         'details' => '1RM 143kg/315lb', 'satisfied_by_item' => '(Ben.) Squat',          'satisfied_by_amount' => 315.0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deadlift 405lb (4 plates)',      'img' => 'deadlift.jpg',      'details' => '1RM 184kg/405lb', 'satisfied_by_item' => '(Ben.) Deadlift',       'satisfied_by_amount' => 405.0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deadlift 315lb (3 plates)',      'img' => 'deadlift.jpg',      'details' => '1RM 143kg/315lb', 'satisfied_by_item' => '(Ben.) Deadlift',       'satisfied_by_amount' => 315.0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Deadlift 225lb (2 plates)',      'img' => 'deadlift.jpg',      'details' => '1RM 102kg/225lb', 'satisfied_by_item' => '(Ben.) Deadlift',       'satisfied_by_amount' => 225.0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('achievements')->whereIn('name', [
            'Bench press 225lb (2 plates)',
            'Overhead press 135lb (1 plate)',
            'Squat 225lb (2 plates)',
            'Squat 315lb (3 plates)',
            'Deadlift 405lb (4 plates)',
            'Deadlift 315lb (3 plates)',
            'Deadlift 225lb (2 plates)',
        ])->delete();
    }
};
