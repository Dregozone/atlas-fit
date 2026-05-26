<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password reset command updates the users password', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'old-password',
    ]);

    $this->artisan('password:reset', [
        'email' => 'test@example.com',
        'newPassword' => 'new-secret-password',
    ])
        ->assertSuccessful()
        ->expectsOutput('Password has been reset for test@example.com.');

    expect(Hash::check('new-secret-password', $user->fresh()->password))->toBeTrue();
});

test('password reset command fails when user is not found', function () {
    $this->artisan('password:reset', [
        'email' => 'nonexistent@example.com',
        'newPassword' => 'some-password',
    ])
        ->assertFailed()
        ->expectsOutput('No user found with email address: nonexistent@example.com');
});
