<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages.dashboard')->name('dashboard');
    Route::livewire('schedule', 'pages.schedule')->name('schedule');
    Route::livewire('workouts', 'pages.workouts')->name('workouts');
    Route::livewire('nutrition', 'pages.nutrition')->name('nutrition');
    Route::livewire('weight', 'pages.weight-tracking')->name('weight');
    Route::livewire('weight/goals', 'pages.body-weight-goals')->name('weight.goals');
    Route::livewire('admin/schedule', 'admin.manage-schedule')->name('admin.schedule');

    Route::livewire('settings/api', 'pages.settings.api')->name('settings.api');
});

require __DIR__.'/settings.php';
