<?php

use App\Models\Consumed;
use App\Models\MealItem;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'api_identifier' => 'test-identifier',
        'api_token' => 'test-token',
        'body_weight_lbs' => 185,
        'fitness_goal' => 'Maintaining',
    ]);
});

test('returns 401 when credentials are invalid', function () {
    $this->getJson('/api/bad-identifier/bad-token/macros')
        ->assertStatus(401)
        ->assertJson(['message' => 'Invalid identifier or token']);
});

test('returns 400 when user has no body weight or fitness goal', function () {
    User::factory()->create([
        'api_identifier' => 'no-goals-id',
        'api_token' => 'no-goals-token',
        'body_weight_lbs' => null,
        'fitness_goal' => null,
    ]);

    $this->getJson('/api/no-goals-id/no-goals-token/macros')
        ->assertStatus(400);
});

test('returns no-store cache control header', function () {
    $response = $this->getJson('/api/test-identifier/test-token/macros')->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('returns macro goals and consumed totals for today', function () {
    $mealItem = MealItem::create([
        'name' => 'Chicken Breast',
        'carbs' => 0.0,
        'protein' => 31.0,
        'fat' => 3.6,
        'calories' => 165.0,
    ]);

    Consumed::create([
        'user_id' => $this->user->id,
        'meal_item_id' => $mealItem->id,
        'quantity' => 2,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/test-identifier/test-token/macros')
        ->assertOk()
        ->assertJsonStructure([
            'consumedToday' => ['carbs', 'protein', 'fat', 'calories'],
            'macroGoals' => ['carbs', 'protein', 'fat', 'calories'],
            'user' => ['id', 'name', 'email'],
        ]);

    expect((float) $response->json('consumedToday.protein'))->toBe(62.0);
    expect((float) $response->json('consumedToday.calories'))->toBe(330.0);
    expect($response->json('user.id'))->toBe($this->user->id);
});

test('consumed totals are zero when nothing has been eaten today', function () {
    $response = $this->getJson('/api/test-identifier/test-token/macros')->assertOk();

    expect((float) $response->json('consumedToday.calories'))->toBe(0.0);
    expect((float) $response->json('consumedToday.protein'))->toBe(0.0);
});
