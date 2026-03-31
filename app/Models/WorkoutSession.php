<?php

namespace App\Models;

use Database\Factories\WorkoutSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['session', 'primary_muscle_group', 'secondary_muscle_group'])]
class WorkoutSession extends Model
{
    /** @use HasFactory<WorkoutSessionFactory> */
    use HasFactory;

    public function primaryWorkouts()
    {
        return $this->hasMany(Workout::class, 'session', 'primary_muscle_group');
    }

    public function secondaryWorkouts()
    {
        return $this->hasMany(Workout::class, 'session', 'secondary_muscle_group');
    }
}
