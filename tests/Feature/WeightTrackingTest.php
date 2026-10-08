<?php

use App\Models\BodyWeight;
use App\Models\BodyWeightGoal;
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

test('logging a first weigh-in celebrates the start of the journey', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', 190)
        ->call('logWeight')
        ->assertHasNoErrors()
        ->assertSee('Weight recorded!')
        ->assertDispatched('celebrate', message: 'First weigh-in logged — the journey starts here!');
});

test('logging a lower weigh-in celebrates the drop', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create([
        'weight_in_lbs' => 190,
        'created_at' => now()->subDay(),
    ]);

    Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', 188.5)
        ->call('logWeight')
        ->assertDispatched('celebrate', message: 'Down 1.5 lbs since your last weigh-in!');
});

test('logging a higher weigh-in still encourages consistency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create([
        'weight_in_lbs' => 190,
        'created_at' => now()->subDay(),
    ]);

    Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', 191)
        ->call('logWeight')
        ->assertDispatched('celebrate', message: 'Weigh-in logged — consistency is what counts.');
});

test('the weight page shows journey progress when goals are set', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeightGoal::create([
        'user_id' => $user->id,
        'start_weight' => 200,
        'end_goal_weight' => 180,
        'milestone_goal_weight' => 190,
        'milestone_date' => now()->addMonth()->toDateString(),
    ]);

    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 190]);

    $this->get(route('weight'))
        ->assertOk()
        ->assertSee('Your journey')
        ->assertSee('50%')
        ->assertSee('Over halfway there');
});

test('saving body weight goals celebrates', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.body-weight-goals')
        ->set('startWeight', 200)
        ->set('endGoalWeight', 180)
        ->set('milestoneGoalWeight', 190)
        ->set('milestoneDate', now()->addMonth()->toDateString())
        ->call('saveGoals')
        ->assertHasNoErrors()
        ->assertSee('Goals saved successfully!')
        ->assertDispatched('celebrate');
});
