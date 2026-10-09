<?php

use App\Models\Rotation;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.schedule'))->assertRedirect(route('login'));
});

test('non-admin users cannot access the manage schedule page', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get(route('admin.schedule'))->assertForbidden();
});

test('admins can access the manage schedule page', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.schedule'))
        ->assertOk()
        ->assertSee('Manage Schedule');
});

test('non-admin users cannot mount the manage schedule component', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    Livewire::test('admin.manage-schedule')->assertForbidden();
});

test('admins can update a rotation', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $rotation = Rotation::where('week', 1)->firstOrFail();

    Livewire::test('admin.manage-schedule')
        ->call('editRotation', $rotation->id)
        ->set('rotationProgram', 'Strength')
        ->call('saveRotation')
        ->assertHasNoErrors()
        ->assertSee('Rotation updated.');

    expect($rotation->fresh()->program)->toBe('Strength');
});
