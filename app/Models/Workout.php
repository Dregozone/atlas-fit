<?php

namespace App\Models;

use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['session', 'exercise_no', 'equipment', 'weight_1rm'])]
class Workout extends Model
{
    /** @use HasFactory<WorkoutFactory> */
    use HasFactory;
}
