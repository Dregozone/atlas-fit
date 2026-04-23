<?php

use App\Models\MealItem;
use App\Models\Consumed;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('nutrition'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the nutrition page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('nutrition'))->assertOk();
});

test('a new food item can be added to the catalogue', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.nutrition')
        ->set('showAddItemForm', true)
        ->set('newItemName', 'Test Chicken Breast (100g)')
        ->set('newItemProtein', 31)
        ->set('newItemCarbs', 0)
        ->set('newItemFat', 3.6)
        ->call('addMealItem')
        ->assertHasNoErrors();

    expect(MealItem::where('name', 'Test Chicken Breast (100g)')->where('is_active', true)->exists())->toBeTrue();
});

test('calories are auto-calculated from protein, carbs and fat', function () {
    $this->actingAs(User::factory()->create());

    // protein 30g × 4 = 120, carbs 50g × 4 = 200, fat 10g × 9 = 90 → total 410 kcal
    Livewire::test('pages.nutrition')
        ->set('showAddItemForm', true)
        ->set('newItemName', 'Calorie Test Item')
        ->set('newItemProtein', 30)
        ->set('newItemCarbs', 50)
        ->set('newItemFat', 10)
        ->call('addMealItem')
        ->assertHasNoErrors();

    $item = MealItem::where('name', 'Calorie Test Item')->where('is_active', true)->first();

    expect($item)->not->toBeNull();
    expect($item->calories)->toEqual(410.0);
});

test('adding a food item requires a name', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.nutrition')
        ->set('showAddItemForm', true)
        ->set('newItemName', '')
        ->set('newItemProtein', 10)
        ->set('newItemCarbs', 10)
        ->set('newItemFat', 5)
        ->call('addMealItem')
        ->assertHasErrors(['newItemName']);
});

test('after adding a food item it appears in the food catalogue', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('pages.nutrition')
        ->set('showAddItemForm', true)
        ->set('newItemName', 'Brand New Food')
        ->set('newItemProtein', 20)
        ->set('newItemCarbs', 30)
        ->set('newItemFat', 5)
        ->call('addMealItem')
        ->assertHasNoErrors();

    // The food items computed property should now include the new item
    expect(MealItem::where('name', 'Brand New Food')->where('is_active', true)->exists())->toBeTrue();
});

test('quick add uses the selected quantity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = MealItem::factory()->create([
        'name' => 'Quick Add Quantity Item',
        'carbs' => 10,
        'protein' => 10,
        'fat' => 10,
        'calories' => 170,
        'is_active' => true,
    ]);

    Livewire::test('pages.nutrition')
        ->set("quickAddQuantities.{$item->id}", 4)
        ->call('quickAdd', $item->id)
        ->assertSee('4 servings of')
        ->assertSet("quickAddQuantities.{$item->id}", 1);

    $consumed = Consumed::query()
        ->where('user_id', $user->id)
        ->firstWhere('meal_item_id', $item->id);

    expect($consumed)->not->toBeNull();
    expect($consumed->quantity)->toBe(4);
});

test('quick add quantity is clamped between 1 and 10', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = MealItem::factory()->create([
        'name' => 'Quick Add Clamp Item',
        'carbs' => 10,
        'protein' => 10,
        'fat' => 10,
        'calories' => 170,
        'is_active' => true,
    ]);

    Livewire::test('pages.nutrition')
        ->set("quickAddQuantities.{$item->id}", 25)
        ->call('quickAdd', $item->id)
        ->assertSet("quickAddQuantities.{$item->id}", 1);

    Livewire::test('pages.nutrition')
        ->set("quickAddQuantities.{$item->id}", 0)
        ->call('quickAdd', $item->id)
        ->assertSet("quickAddQuantities.{$item->id}", 1);

    $quantities = Consumed::query()
        ->where('user_id', $user->id)
        ->where('meal_item_id', $item->id)
        ->orderBy('id')
        ->pluck('quantity')
        ->all();

    expect($quantities)->toBe([10, 1]);
});
