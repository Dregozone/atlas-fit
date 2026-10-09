<?php

use App\Models\CompletedWorkout;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('workouts'))->assertRedirect(route('login'));
});

test('authenticated users can visit the workouts page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('workouts'))
        ->assertOk()
        ->assertSee('Log a Workout')
        ->assertSee('No workouts logged yet. Get after it!');
});

test('logging a workout shows a success message and celebrates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages.workouts')
        ->set('equipment', '(Ben.) Squat')
        ->set('sets', 5)
        ->set('reps', 5)
        ->set('weight', 225)
        ->call('logWorkout')
        ->assertHasNoErrors()
        ->assertSee('Workout logged successfully!')
        ->assertDispatched('celebrate');

    expect(CompletedWorkout::where('user_id', $user->id)->where('equipment', '(Ben.) Squat')->exists())->toBeTrue();
});

test('recent workouts are grouped under a today heading', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    CompletedWorkout::create([
        'user_id' => $user->id,
        'equipment' => 'Leg press',
        'sets' => 3,
        'reps' => 12,
        'weight' => 200,
        'is_deleted' => false,
    ]);

    Livewire::test('pages.workouts')
        ->assertSee('Today')
        ->assertSee('Leg press')
        ->assertSee('3 sets × 12 reps');
});

test('a failed workout submission does not celebrate', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages.workouts')
        ->set('equipment', '')
        ->call('logWorkout')
        ->assertHasErrors(['equipment'])
        ->assertNotDispatched('celebrate');
});
