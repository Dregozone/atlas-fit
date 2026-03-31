<?php

namespace App\Models;

use Database\Factories\DayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['day', 'session'])]
class Day extends Model
{
    /** @use HasFactory<DayFactory> */
    use HasFactory;

    public function workoutSession()
    {
        return $this->belongsTo(WorkoutSession::class, 'session', 'session');
    }
}
