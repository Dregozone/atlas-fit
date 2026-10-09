<?php

use App\Models\Consumed;
use App\Models\MealItem;
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

test('nutrition page includes mobile catalogue guidance', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('nutrition'))
        ->assertOk()
        ->assertSee('Tap an item name to view its full text.');
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

test('quick adding a food item celebrates', function () {
    $this->actingAs(User::factory()->create());

    $item = MealItem::factory()->create([
        'name' => 'Celebration Oats',
        'carbs' => 10,
        'protein' => 10,
        'fat' => 10,
        'calories' => 170,
        'is_active' => true,
    ]);

    Livewire::test('pages.nutrition')
        ->call('quickAdd', $item->id)
        ->assertDispatched('celebrate', message: 'Celebration Oats logged — nicely fuelled!');
});

test('adding a food item to the catalogue celebrates', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.nutrition')
        ->set('showAddItemForm', true)
        ->set('newItemName', 'Celebration Item')
        ->set('newItemProtein', 10)
        ->call('addMealItem')
        ->assertHasNoErrors()
        ->assertSee('Food item added!')
        ->assertDispatched('celebrate');
});

test('an empty food diary shows an encouraging empty state', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('nutrition'))
        ->assertOk()
        ->assertSee('Nothing logged today yet.')
        ->assertSee('New food');
});

test('a food can be removed from today\'s diary', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $item = MealItem::factory()->create([
        'name' => 'Removable Porridge',
        'carbs' => 10,
        'protein' => 10,
        'fat' => 10,
        'calories' => 170,
        'is_active' => true,
    ]);
    $keptItem = MealItem::factory()->create([
        'name' => 'Kept Banana',
        'carbs' => 27,
        'protein' => 1,
        'fat' => 0,
        'calories' => 112,
        'is_active' => true,
    ]);

    Consumed::create(['user_id' => $user->id, 'meal_item_id' => $item->id, 'quantity' => 2]);
    Consumed::create(['user_id' => $user->id, 'meal_item_id' => $item->id, 'quantity' => 1]);
    Consumed::create(['user_id' => $user->id, 'meal_item_id' => $keptItem->id, 'quantity' => 1]);
    Consumed::create(['user_id' => $otherUser->id, 'meal_item_id' => $item->id, 'quantity' => 1]);
    $yesterday = Consumed::create(['user_id' => $user->id, 'meal_item_id' => $item->id, 'quantity' => 1]);
    $yesterday->forceFill(['created_at' => now()->subDay()])->save();

    Livewire::test('pages.nutrition')
        ->assertSee('Removable Porridge')
        ->call('removeConsumed', $item->id)
        ->assertSee('Kept Banana');

    $todaysEntries = Consumed::query()
        ->where('user_id', $user->id)
        ->where('meal_item_id', $item->id)
        ->whereDate('created_at', today())
        ->count();

    expect($todaysEntries)->toBe(0);
    expect(Consumed::where('user_id', $user->id)->where('meal_item_id', $keptItem->id)->exists())->toBeTrue();
    expect(Consumed::where('user_id', $otherUser->id)->where('meal_item_id', $item->id)->exists())->toBeTrue();
    expect($yesterday->fresh())->not->toBeNull();
});
