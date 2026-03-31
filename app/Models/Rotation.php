<?php

namespace App\Models;

use Database\Factories\RotationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['week', 'program', 'sets', 'reps', 'weight_percent'])]
class Rotation extends Model
{
    /** @use HasFactory<RotationFactory> */
    use HasFactory;
}
