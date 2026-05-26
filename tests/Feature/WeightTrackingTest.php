<?php

use App\Models\BodyWeight;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('weight'))->assertRedirect(route('login'));
});

test('authenticated users can visit the weight tracking page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('weight'))->assertOk();
});

test('weight chart defaults to last month and averages repeated entries for each day', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create([
        'weight_in_lbs' => 201.2,
        'created_at' => now()->subDays(12)->setTime(8, 0),
    ]);

    BodyWeight::factory()->for($user)->create([
        'weight_in_lbs' => 199.2,
        'created_at' => now()->subDays(12)->setTime(18, 0),
    ]);

    BodyWeight::factory()->for($user)->create([
        'weight_in_lbs' => 198.0,
        'created_at' => now()->subDays(2),
    ]);

    BodyWeight::factory()->for($user)->create([
        'weight_in_lbs' => 240.0,
        'created_at' => now()->subDays(45),
    ]);

    $chartData = Livewire::test('pages.weight-tracking')
        ->assertSet('chartRange', '1m')
        ->get('chartData');

    expect($chartData)->toHaveCount(2);
    expect($chartData[0])->toHaveKeys(['label', 'weight', 'date']);
    expect(collect($chartData)->pluck('weight')->contains(240.0))->toBeFalse();
    expect($chartData[0]['weight'])->toBe(200.2);
    expect($chartData[1]['weight'])->toBe(198.0);
    expect($chartData[0]['label'])->toMatch('/^\d{1,2}\s\w{3}$/');
    expect($chartData[0]['date'])->toMatch('/^\d{1,2}\s\w{3}\s\d{4}$/');
});

test('weight chart can switch to multi-day chunking for longer ranges', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $threeMonthChartChunkSize = 3;
    $entryOffsets = [80, 79, 78, 77, 76, 75];

    foreach ($entryOffsets as $index => $offsetDays) {
        BodyWeight::factory()->for($user)->create([
            'weight_in_lbs' => 180 + $index,
            'created_at' => now()->subDays($offsetDays),
        ]);
    }

    $component = Livewire::test('pages.weight-tracking')
        ->call('setChartRange', '3m')
        ->assertSet('chartRange', '3m');

    $chartData = $component->get('chartData');

    expect($chartData)->toHaveCount((int) ceil(count($entryOffsets) / $threeMonthChartChunkSize));
    expect($chartData[0]['weight'])->toBe(181.0);
    expect($chartData[1]['weight'])->toBe(184.0);
});

test('invalid chart range requests are ignored', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages.weight-tracking')
        ->assertSet('chartRange', '1m')
        ->call('setChartRange', 'invalid')
        ->assertSet('chartRange', '1m');
});
