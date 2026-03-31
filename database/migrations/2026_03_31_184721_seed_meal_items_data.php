<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('meal_items')->exists()) {
            // Only insert new items that don't already exist by name
            $existing = DB::table('meal_items')->pluck('name')->all();

            $newItems = array_filter([
                ['img' => null, 'name' => 'Huel Black (2 scoops)', 'carbs' => 23,   'protein' => 40,   'fat' => 18,   'calories' => 414, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Tin of Tuna',           'carbs' => 0,    'protein' => 28,   'fat' => 1.5,  'calories' => 125, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Ravioli',               'carbs' => 51,   'protein' => 10,   'fat' => 6,    'calories' => 298, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Banana (1 medium)',     'carbs' => 27,   'protein' => 1.3,  'fat' => 0.4,  'calories' => 107, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Granola with semi-skimmed milk', 'carbs' => 39, 'protein' => 9, 'fat' => 11, 'calories' => 290, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Tuna mayo wrap',        'carbs' => 38,   'protein' => 18,   'fat' => 14,   'calories' => 350, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Packet of crisps (25g)', 'carbs' => 16,  'protein' => 1.6,  'fat' => 7,    'calories' => 130, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Snack-sized chocolate bar', 'carbs' => 12, 'protein' => 1.6, 'fat' => 6,   'calories' => 110, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => '1/4 Quiche lorraine',   'carbs' => 18,   'protein' => 8.7,  'fat' => 20,   'calories' => 293, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => "Sainsbury's sausage roll", 'carbs' => 16.4, 'protein' => 5.3, 'fat' => 10.7, 'calories' => 188, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Strawberry Nesquik milkshake (200ml)', 'carbs' => 21, 'protein' => 4.5, 'fat' => 3, 'calories' => 130, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Banana, milk and ice shake', 'carbs' => 37, 'protein' => 8, 'fat' => 4, 'calories' => 205, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Chicken, lettuce, mayo & chilli jam wrap', 'carbs' => 48, 'protein' => 31, 'fat' => 20, 'calories' => 493, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['img' => null, 'name' => 'Bagel with honey roasted ham & mayo', 'carbs' => 45, 'protein' => 17, 'fat' => 18, 'calories' => 407, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ], fn ($item) => ! in_array($item['name'], $existing));

            if (! empty($newItems)) {
                DB::table('meal_items')->insert(array_values($newItems));
            }

            return;
        }

        DB::table('meal_items')->insert([
            // ── Existing items ────────────────────────────────────────────────
            ['img' => null, 'name' => 'Huel Black (2 scoops)', 'carbs' => 23,   'protein' => 40,   'fat' => 18,   'calories' => 414, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['img' => null, 'name' => 'Tin of Tuna',           'carbs' => 0,    'protein' => 28,   'fat' => 1.5,  'calories' => 125, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['img' => null, 'name' => 'Ravioli',               'carbs' => 51,   'protein' => 10,   'fat' => 6,    'calories' => 298, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // ── New items ─────────────────────────────────────────────────────
            // Banana (1 medium, ~120g) — USDA Standard Reference
            ['img' => null, 'name' => 'Banana (1 medium)',     'carbs' => 27,   'protein' => 1.3,  'fat' => 0.4,  'calories' => 107, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Granola (50g) with semi-skimmed milk (150ml) — typical UK values
            ['img' => null, 'name' => 'Granola with semi-skimmed milk', 'carbs' => 39, 'protein' => 9, 'fat' => 11, 'calories' => 290, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Tuna mayo wrap (~200g, typical UK supermarket style)
            ['img' => null, 'name' => 'Tuna mayo wrap',        'carbs' => 38,   'protein' => 18,   'fat' => 14,   'calories' => 350, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Walkers Ready Salted crisps (25g bag) — label values
            ['img' => null, 'name' => 'Packet of crisps (25g)', 'carbs' => 16,  'protein' => 1.6,  'fat' => 7,    'calories' => 130, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Snack-sized chocolate bar (~21g, Cadbury Dairy Milk style)
            ['img' => null, 'name' => 'Snack-sized chocolate bar', 'carbs' => 12, 'protein' => 1.6, 'fat' => 6,   'calories' => 110, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // 1/4 quiche lorraine (~100g serving of a standard 400g quiche)
            ['img' => null, 'name' => '1/4 Quiche lorraine',   'carbs' => 18,   'protein' => 8.7,  'fat' => 20,   'calories' => 293, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Sainsbury's sausage roll (60g, from standard multipack) — Sainsbury's label
            ['img' => null, 'name' => "Sainsbury's sausage roll", 'carbs' => 16.4, 'protein' => 5.3, 'fat' => 10.7, 'calories' => 188, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Nestlé Nesquik Strawberry milkshake (200ml ready-to-drink carton)
            ['img' => null, 'name' => 'Strawberry Nesquik milkshake (200ml)', 'carbs' => 21, 'protein' => 4.5, 'fat' => 3, 'calories' => 130, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Banana, semi-skimmed milk and ice shake (1 medium banana + 200ml milk)
            ['img' => null, 'name' => 'Banana, milk and ice shake', 'carbs' => 37, 'protein' => 8, 'fat' => 4, 'calories' => 205, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Chicken and lettuce with mayo and chilli jam wrap (large tortilla, 80g chicken, 20g mayo, 15g chilli jam)
            ['img' => null, 'name' => 'Chicken, lettuce, mayo & chilli jam wrap', 'carbs' => 48, 'protein' => 31, 'fat' => 20, 'calories' => 493, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Bagel with honey roasted ham, lettuce and mayo (95g bagel, 50g ham, 20g mayo)
            ['img' => null, 'name' => 'Bagel with honey roasted ham & mayo', 'carbs' => 45, 'protein' => 17, 'fat' => 18, 'calories' => 407, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('meal_items')->whereIn('name', [
            'Huel Black (2 scoops)',
            'Tin of Tuna',
            'Ravioli',
            'Banana (1 medium)',
            'Granola with semi-skimmed milk',
            'Tuna mayo wrap',
            'Packet of crisps (25g)',
            'Snack-sized chocolate bar',
            '1/4 Quiche lorraine',
            "Sainsbury's sausage roll",
            'Strawberry Nesquik milkshake (200ml)',
            'Banana, milk and ice shake',
            'Chicken, lettuce, mayo & chilli jam wrap',
            'Bagel with honey roasted ham & mayo',
        ])->delete();
    }
};
