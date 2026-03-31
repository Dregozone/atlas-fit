<?php

namespace App\Models;

use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'img', 'details', 'satisfied_by_item', 'satisfied_by_amount'])]
class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;
}
