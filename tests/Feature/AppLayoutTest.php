<?php

use App\Models\User;

test('the app layout includes a skip link and mobile tab navigation', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Skip to main content')
        ->assertSee('id="main-content"', false)
        ->assertSee('aria-label="Primary"', false)
        ->assertSee(route('schedule'))
        ->assertSee(route('workouts'))
        ->assertSee(route('nutrition'))
        ->assertSee(route('weight'));
});

test('the app layout no longer links to starter kit resources', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('github.com/laravel/livewire-starter-kit')
        ->assertDontSee('laravel.com/docs/starter-kits');
});

test('the manage schedule link is only shown to admins', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));
    $this->get(route('dashboard'))->assertDontSee('Manage Schedule');

    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get(route('dashboard'))->assertSee('Manage Schedule');
});

test('each main page renders inside the redesigned layout', function (string $routeName, string $heading) {
    $this->actingAs(User::factory()->create());

    $this->get(route($routeName))
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('data-af-app', false);
})->with([
    'schedule' => ['schedule', 'Programme Schedule'],
    'workouts' => ['workouts', 'Workouts'],
    'nutrition' => ['nutrition', 'Nutrition'],
    'weight' => ['weight', 'Weight Tracking'],
    'weight goals' => ['weight.goals', 'Body Weight Goals'],
    'api settings' => ['settings.api', 'API Access'],
]);
