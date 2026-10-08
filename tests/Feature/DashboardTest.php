<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows the rest day message on rest days', function () {
    // Tuesdays are seeded as rest days.
    $this->travelTo(now()->next('Tuesday')->setTime(9, 0));
    $this->actingAs(User::factory()->create(['name' => 'Jamie Lifter']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Good morning')
        ->assertSee('Jamie')
        ->assertSee('Rest day — recovery is part of the programme.');
});

test('the dashboard shows today\'s session with rotation sets and reps on training days', function () {
    // Mondays are seeded with session "a" (Chest + Shoulders).
    $this->travelTo(now()->next('Monday')->setTime(15, 0));
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Good afternoon')
        ->assertSee('Chest &amp; Shoulders', false)
        ->assertSee('sets ×', false)
        ->assertSee('Start logging');
});

test('the dashboard shows macro rings when a fitness profile is set', function () {
    $this->actingAs(User::factory()->create([
        'body_weight_lbs' => 180,
        'fitness_goal' => 'Maintaining',
    ]));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Calories eaten today')
        ->assertSee('kcal left')
        ->assertDontSee('to see macro targets');
});

test('the dashboard prompts users without a fitness profile to set one', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Set your profile goals')
        ->assertSee('to see macro targets');
});
