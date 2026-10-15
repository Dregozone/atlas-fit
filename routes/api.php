<?php

use App\Http\Controllers\Api\DailyStats;
use App\Http\Controllers\Api\Macros;
use App\Http\Controllers\Api\UpcomingWorkouts;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/{identifier}/{token}/workouts', UpcomingWorkouts::class);
Route::get('/{identifier}/{token}/macros', Macros::class);
Route::get('/stats/daily', DailyStats::class)
    ->middleware('throttle:30,1');
