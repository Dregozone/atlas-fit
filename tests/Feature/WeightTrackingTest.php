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
    expect($chartData[0]['date'])->toBe(now()->subDays(12)->toDateString());
    expect($chartData[0]['label'])->toBe(now()->subDays(12)->format('D j M Y').' (average of 2 weigh-ins)');
    expect($chartData[1]['label'])->toBe(now()->subDays(2)->format('D j M Y'));
});

test('longer chart ranges plot every weigh-in day instead of averaging them away', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entryOffsets = [80, 79, 78, 77, 76, 75];

    foreach ($entryOffsets as $index => $offsetDays) {
        BodyWeight::factory()->for($user)->create([
            'weight_in_lbs' => 180 + $index,
            'created_at' => now()->subDays($offsetDays),
        ]);
    }

    foreach (['3m', '6m', '1y', 'all'] as $range) {
        $chartData = Livewire::test('pages.weight-tracking')
            ->call('setChartRange', $range)
            ->assertSet('chartRange', $range)
            ->get('chartData');

        expect(collect($chartData)->pluck('weight')->all())->toBe([180.0, 181.0, 182.0, 183.0, 184.0, 185.0]);
    }
});

test('since joining switches to weekly points when there are too many days to plot', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $start = now()->subDays(399)->startOfDay()->setTime(8, 0);

    BodyWeight::factory()->for($user)->createMany(
        collect(range(0, 399))->map(fn (int $day) => [
            'weight_in_lbs' => 200,
            'created_at' => $start->copy()->addDays($day),
        ])->all()
    );

    $component = Livewire::test('pages.weight-tracking')->call('setChartRange', 'all');
    $chartData = $component->get('chartData');

    expect(count($chartData))->toBeLessThan(60);
    expect($chartData[0]['label'])->toStartWith('Week of ');
    expect($component->get('chartRangeResolution'))->toBe('one point per week (weekly average)');
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

test('the weight input starts empty and is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.weight-tracking')
        ->assertSet('weightInLbs', null)
        ->call('logWeight')
        ->assertHasErrors(['weightInLbs' => 'required']);
});

test('the required loss rate is measured from the latest weigh-in', function () {
    $this->travelTo(now()->startOfDay()->setTime(12, 0));

    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeightGoal::create([
        'user_id' => $user->id,
        'start_weight' => 200,
        'end_goal_weight' => 170,
        'milestone_goal_weight' => 180,
        'milestone_date' => now()->addDays(10)->toDateString(),
    ]);

    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 190]);

    $goalStats = Livewire::test('pages.weight-tracking')->get('goalStats');

    $daysRemaining = $goalStats['days_remaining'];

    expect($goalStats['latest_weight'])->toEqual(190.0);
    expect($goalStats['required_loss_per_day'])->toEqual(round((190 - 180) / $daysRemaining, 2));
});

test('the required loss rate falls back to the start weight without weigh-ins', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeightGoal::create([
        'user_id' => $user->id,
        'start_weight' => 200,
        'end_goal_weight' => 170,
        'milestone_goal_weight' => 180,
        'milestone_date' => now()->addDays(20)->toDateString(),
    ]);

    $goalStats = Livewire::test('pages.weight-tracking')->get('goalStats');

    expect($goalStats['latest_weight'])->toEqual(200.0);
    expect($goalStats['required_loss_per_day'])->toEqual(round((200 - 180) / $goalStats['days_remaining'], 2));
});

test('the required loss rate updates after logging a new weigh-in', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeightGoal::create([
        'user_id' => $user->id,
        'start_weight' => 200,
        'end_goal_weight' => 170,
        'milestone_goal_weight' => 180,
        'milestone_date' => now()->addDays(20)->toDateString(),
    ]);

    $component = Livewire::test('pages.weight-tracking');
    expect($component->get('goalStats')['latest_weight'])->toEqual(200.0);

    $component->set('weightInLbs', 195)->call('logWeight');

    expect($component->get('goalStats')['latest_weight'])->toEqual(195.0);
});

test('chart ranges without weigh-ins on two different days are unavailable', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 190, 'created_at' => now()->subDays(100)]);
    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 188, 'created_at' => now()->subDays(5)]);

    $component = Livewire::test('pages.weight-tracking');

    expect(collect($component->get('chartRangeButtons'))->pluck('is_available', 'value')->all())->toBe([
        '1m' => false,
        '3m' => false,
        '6m' => true,
        '1y' => true,
        'all' => true,
    ]);

    $component
        ->assertSet('chartRange', '6m')
        ->call('setChartRange', '1m')
        ->assertSet('chartRange', '6m');
});

test('the chart shows the high and low for the period', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    foreach ([[10, 190], [8, 193.4], [5, 186.2], [2, 188]] as [$daysAgo, $weight]) {
        BodyWeight::factory()->for($user)->create(['weight_in_lbs' => $weight, 'created_at' => now()->subDays($daysAgo)]);
    }

    $highLow = Livewire::test('pages.weight-tracking')->get('chartHighLow');

    expect($highLow['high'])->toBe(['weight' => 193.4, 'date' => now()->subDays(8)->format('j M Y')]);
    expect($highLow['low'])->toBe(['weight' => 186.2, 'date' => now()->subDays(5)->format('j M Y')]);
});

test('weights outside the allowed range are rejected', function (float $weight) {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', $weight)
        ->call('logWeight')
        ->assertHasErrors('weightInLbs');

    expect(BodyWeight::count())->toBe(0);
})->with([
    'decimal slipped left' => [18.5],
    'decimal slipped right' => [1855],
]);

test('a weigh-in close to the last one is saved without asking', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 200, 'created_at' => now()->subDay()]);

    Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', 210)
        ->call('logWeight')
        ->assertHasNoErrors()
        ->assertSet('showWeightConfirmation', false);

    expect($user->bodyWeights()->count())->toBe(2);
});

test('an unusual weigh-in asks for confirmation before it is saved', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 200, 'created_at' => now()->subDay()]);

    $component = Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', 180)
        ->call('logWeight')
        ->assertHasNoErrors()
        ->assertSet('showWeightConfirmation', true)
        ->assertSet('weightConfirmationMessage', '20 lbs (10%) lighter than your last weigh-in of 200 lbs on '.now()->subDay()->format('j M Y').'.');

    expect($user->bodyWeights()->count())->toBe(1);

    $component
        ->call('confirmLogWeight')
        ->assertSet('showWeightConfirmation', false)
        ->assertDispatched('celebrate');

    expect($user->bodyWeights()->latest('id')->first()->weight_in_lbs)->toEqual(180.0);
});

test('a weigh-in far from a recent one is blocked, even when confirmed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 200, 'created_at' => now()->subDays(3)]);

    Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', 85)
        ->call('logWeight')
        ->assertHasErrors('weightInLbs')
        ->assertSet('showWeightConfirmation', false)
        ->call('confirmLogWeight')
        ->assertHasErrors('weightInLbs');

    expect($user->bodyWeights()->count())->toBe(1);
});

test('a large change since a weigh-in months ago only asks for confirmation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 250, 'created_at' => now()->subDays(120)]);

    Livewire::test('pages.weight-tracking')
        ->set('weightInLbs', 190)
        ->call('logWeight')
        ->assertHasNoErrors()
        ->assertSet('showWeightConfirmation', true);
});

test('users can correct one of their weigh-ins', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 18.5]);

    Livewire::test('pages.weight-tracking')
        ->call('editWeight', $entry->id)
        ->assertSet('showEditWeight', true)
        ->assertSet('editWeightInLbs', 18.5)
        ->set('editWeightInLbs', 1855)
        ->call('updateWeight')
        ->assertHasErrors('editWeightInLbs')
        ->set('editWeightInLbs', 185.5)
        ->call('updateWeight')
        ->assertHasNoErrors()
        ->assertSet('showEditWeight', false);

    expect($entry->fresh()->weight_in_lbs)->toEqual(185.5);
});

test('users can delete one of their weigh-ins', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = BodyWeight::factory()->for($user)->create(['weight_in_lbs' => 190]);

    Livewire::test('pages.weight-tracking')
        ->call('confirmDeleteWeight', $entry->id)
        ->assertSet('showDeleteWeight', true)
        ->call('deleteWeight')
        ->assertSet('showDeleteWeight', false);

    expect($entry->fresh())->toBeNull();
});

test('users cannot edit or delete another user\'s weigh-ins', function (string $method) {
    $entry = BodyWeight::factory()->for(User::factory())->create(['weight_in_lbs' => 190]);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages.weight-tracking')
        ->call($method, $entry->id)
        ->assertNotFound();

    expect($entry->fresh()->weight_in_lbs)->toEqual(190.0);
})->with(['editWeight', 'confirmDeleteWeight']);

test('users cannot save or delete another user\'s weigh-in by changing the id', function () {
    $entry = BodyWeight::factory()->for(User::factory())->create(['weight_in_lbs' => 190]);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages.weight-tracking')
        ->set('editingWeightId', $entry->id)
        ->set('editWeightInLbs', 150)
        ->call('updateWeight')
        ->assertNotFound();

    Livewire::test('pages.weight-tracking')
        ->set('deletingWeightId', $entry->id)
        ->call('deleteWeight')
        ->assertNotFound();

    expect($entry->fresh()->weight_in_lbs)->toEqual(190.0);
});
