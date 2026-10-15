<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['stats.token' => 'stats-secret']);
});

test('daily stats include signups logins and active user windows', function () {
    $activeSevenDays = User::factory()->create([
        'created_at' => '2026-10-12 12:00:00',
        'last_active_at' => '2026-10-12 12:00:00',
    ]);
    $activeThirtyDays = User::factory()->create([
        'created_at' => '2026-10-01 12:00:00',
        'last_active_at' => '2026-10-01 12:00:00',
    ]);
    User::factory()->create([
        'created_at' => '2026-09-01 12:00:00',
        'last_active_at' => '2026-09-12 22:59:59',
    ]);
    User::factory()->create([
        'created_at' => '2026-10-13 00:00:00',
        'last_active_at' => '2026-10-12 23:00:00',
    ]);

    DB::table('logins')->insert([
        ['user_id' => $activeSevenDays->id, 'created_at' => '2026-10-12 10:00:00', 'updated_at' => '2026-10-12 10:00:00'],
        ['user_id' => $activeSevenDays->id, 'created_at' => '2026-10-12 11:00:00', 'updated_at' => '2026-10-12 11:00:00'],
        ['user_id' => $activeThirtyDays->id, 'created_at' => '2026-10-12 12:00:00', 'updated_at' => '2026-10-12 12:00:00'],
    ]);

    $this->withToken('stats-secret')
        ->getJson('/api/stats/daily?date=2026-10-12')
        ->assertExactJson([
            'app' => 'Atlas Fit',
            'date' => '2026-10-12',
            'signups' => 1,
            'logins' => ['total' => 3, 'unique' => 2],
            'users' => ['total' => 4, 'active_7d' => 1, 'active_30d' => 2],
        ]);
});

test('daily stats default to yesterday in London', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 00:30:00', 'UTC'));

    $this->withToken('stats-secret')
        ->getJson('/api/stats/daily')
        ->assertOk()
        ->assertJsonPath('date', '2026-10-14');
});

test('daily stats require the configured bearer token', function () {
    $this->getJson('/api/stats/daily')
        ->assertUnauthorized();

    $this->withToken('wrong-token')
        ->getJson('/api/stats/daily')
        ->assertUnauthorized();
});

test('daily stats are unavailable when no token is configured', function () {
    config(['stats.token' => null]);

    $this->getJson('/api/stats/daily')
        ->assertNotFound();
});

test('daily stats reject dates outside the required format', function () {
    $this->withToken('stats-secret')
        ->getJson('/api/stats/daily?date=12-10-2026')
        ->assertUnprocessable();
});

test('successful logins are recorded without request metadata', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(DB::table('logins')->where('user_id', $user->id)->count())->toBe(1)
        ->and(Schema::getColumnListing('logins'))->toBe(['id', 'user_id', 'created_at', 'updated_at']);
});

test('authenticated activity updates at most once an hour', function () {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-10-12 12:00:00', 'UTC'));

    $this->actingAs($user)->get('/dashboard');
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-10-12 12:00:00');

    $this->travelTo(CarbonImmutable::parse('2026-10-12 12:59:59', 'UTC'));
    $this->actingAs($user)->get('/dashboard');
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-10-12 12:00:00');

    $this->travelTo(CarbonImmutable::parse('2026-10-12 13:00:00', 'UTC'));
    $this->actingAs($user)->get('/dashboard');
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-10-12 13:00:00');
});
