<?php

use App\Models\User;
use App\Models\UserDay;
use App\Models\Workout;
use Carbon\Carbon;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('schedule'))->assertRedirect(route('login'));
});

test('editing starts from the user\'s current day plan', function () {
    $user = User::factory()->create();
    UserDay::factory()->for($user)->create(['day' => 'Tuesday', 'session' => 'c']);
    $this->actingAs($user);

    $dayPlan = Livewire::test('pages.schedule')
        ->call('startEditing')
        ->assertSet('isEditing', true)
        ->get('dayPlan');

    expect($dayPlan)->toBe([
        'Monday' => 'a',
        'Tuesday' => 'c',
        'Wednesday' => 'b',
        'Thursday' => 'rest',
        'Friday' => 'c',
        'Saturday' => 'rest',
        'Sunday' => 'rest',
    ]);
});

test('users can move a day to another session or a rest day', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages.schedule')
        ->call('startEditing')
        ->set('dayPlan.Saturday', 'b')
        ->set('dayPlan.Monday', 'rest')
        ->assertHasNoErrors();

    expect($user->scheduleDays()->orderBy('day')->pluck('session', 'day')->all())->toBe([
        'Monday' => null,
        'Saturday' => 'b',
    ]);
});

test('choosing the programme\'s own session for a day removes the change', function () {
    $user = User::factory()->create();
    UserDay::factory()->for($user)->create(['day' => 'Monday', 'session' => null]);
    $this->actingAs($user);

    Livewire::test('pages.schedule')
        ->call('startEditing')
        ->set('dayPlan.Monday', 'a');

    expect($user->scheduleDays()->count())->toBe(0);
});

test('unknown sessions and days are rejected', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages.schedule')
        ->call('startEditing')
        ->set('dayPlan.Monday', 'z')
        ->assertHasErrors('dayPlan.Monday')
        ->set('dayPlan.Funday', 'a');

    expect($user->scheduleDays()->count())->toBe(0);
});

test('users can switch an exercise off and back on', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $benchPress = Workout::where('equipment', '(Ben.) Bench press')->firstOrFail();

    $component = Livewire::test('pages.schedule')->call('toggleExercise', $benchPress->id);
    expect($user->excludedWorkouts()->pluck('workouts.id')->all())->toBe([$benchPress->id]);

    $component->call('toggleExercise', $benchPress->id);
    expect($user->excludedWorkouts()->count())->toBe(0);
});

test('one user\'s changes do not affect another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages.schedule')
        ->call('toggleExercise', Workout::firstOrFail()->id)
        ->call('startEditing')
        ->set('dayPlan.Monday', 'rest');

    expect($otherUser->excludedWorkouts()->count())->toBe(0)
        ->and($otherUser->scheduleDays()->count())->toBe(0);
});

test('switched off exercises are hidden from the schedule outside edit mode', function () {
    $user = User::factory()->create();
    $benchPress = Workout::where('equipment', '(Ben.) Bench press')->firstOrFail();
    $user->excludedWorkouts()->attach($benchPress);
    $this->actingAs($user);

    Livewire::test('pages.schedule')
        ->assertDontSee('(Ben.) Bench press')
        ->assertSee('1 exercise switched off by you')
        ->call('startEditing')
        ->assertSee('(Ben.) Bench press');
});

test('users can reset their schedule to the programme', function () {
    $user = User::factory()->create();
    $user->excludedWorkouts()->attach(Workout::firstOrFail());
    UserDay::factory()->for($user)->create(['day' => 'Monday', 'session' => null]);
    $this->actingAs($user);

    Livewire::test('pages.schedule')
        ->call('resetToProgramme')
        ->assertDispatched('celebrate');

    expect($user->excludedWorkouts()->count())->toBe(0)
        ->and($user->scheduleDays()->count())->toBe(0);
});

test('the dashboard follows the user\'s day plan and switched off exercises', function () {
    // Mondays are seeded with session "a" (Chest + Shoulders); Saturdays are rest days.
    $this->travelTo(Carbon::parse('2026-07-06 09:00'));

    $user = User::factory()->create();
    $user->excludedWorkouts()->attach(Workout::where('equipment', '(Ben.) Bench press')->firstOrFail());
    $this->actingAs($user);

    $today = Livewire::test('pages.dashboard')->get('todaySchedule');

    expect($today['session']->session)->toBe('a');
    expect(collect($today['primary_exercises'])->pluck('equipment'))->not->toContain('(Ben.) Bench press');

    UserDay::factory()->for($user)->create(['day' => 'Monday', 'session' => null]);

    expect(Livewire::test('pages.dashboard')->get('todaySchedule')['is_rest_day'])->toBeTrue();
});

test('the upcoming workouts API follows the user\'s schedule', function () {
    // Tuesday 2026-07-07 is a rest day in the programme; Wednesday is session "b" (Arms + Back).
    $this->travelTo(Carbon::parse('2026-07-07 09:00'));

    $user = User::factory()->create(['api_identifier' => 'id-1', 'api_token' => 'token-1']);
    UserDay::factory()->for($user)->create(['day' => 'Tuesday', 'session' => 'b']);
    $user->excludedWorkouts()->attach(Workout::where('session', 'Arms')->orderBy('exercise_no')->firstOrFail());

    $response = $this->getJson('/api/id-1/token-1/workouts')->assertOk();

    expect($response->json('upcomingWorkouts'))->toHaveCount(9);
});
