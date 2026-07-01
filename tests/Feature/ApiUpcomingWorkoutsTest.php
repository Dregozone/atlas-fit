<?php

use App\Models\Day;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create([
        'api_identifier' => 'test-identifier',
        'api_token' => 'test-token',
    ]);
});

test('returns 401 when credentials are invalid', function () {
    $this->getJson('/api/bad-identifier/bad-token/workouts')
        ->assertStatus(401)
        ->assertJson(['message' => 'Invalid identifier or token']);
});

test('returns no-store cache control header', function () {
    // Today is Wednesday (seeded with session 'b' — Arms + Back)
    $response = $this->getJson('/api/test-identifier/test-token/workouts')->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('returns workout data for today when a session is scheduled', function () {
    // Today is Wednesday, session 'b' → Arms (5) + Back (5) = 10 exercises
    $response = $this->getJson('/api/test-identifier/test-token/workouts')
        ->assertOk()
        ->assertJsonStructure([
            'rotation',
            'upcomingWorkouts',
            'user' => ['id', 'name', 'email'],
        ]);

    expect($response->json('upcomingWorkouts'))->toHaveCount(10);
    expect($response->json('user.id'))->toBe($this->user->id);
});

test('returns rest day message with next workout on a rest day', function () {
    // Travel to Tuesday 2026-07-07 — session is null in seeded data
    $this->travelTo(Carbon::parse('2026-07-07'));

    $response = $this->getJson('/api/test-identifier/test-token/workouts')
        ->assertOk()
        ->assertJsonFragment(['message' => 'No workout scheduled for today'])
        ->assertJsonStructure([
            'message',
            'nextWorkout' => ['date', 'day', 'rotation', 'upcomingWorkouts'],
        ]);

    // Next workout day after Tuesday is Wednesday
    expect($response->json('nextWorkout.day'))->toBe('Wednesday');
    expect($response->json('nextWorkout.date'))->toBe('2026-07-08');
    expect($response->json('nextWorkout.upcomingWorkouts'))->toHaveCount(10);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('nextWorkout is null when no sessions are scheduled in the next 7 days', function () {
    Day::query()->update(['session' => null]);

    $this->getJson('/api/test-identifier/test-token/workouts')
        ->assertOk()
        ->assertJson([
            'message' => 'No workout scheduled for today',
            'nextWorkout' => null,
        ]);
});
