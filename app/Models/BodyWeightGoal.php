<?php

namespace App\Models;

use Database\Factories\BodyWeightGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'start_weight', 'end_goal_weight', 'milestone_goal_weight', 'milestone_date'])]
class BodyWeightGoal extends Model
{
    /** @use HasFactory<BodyWeightGoalFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'milestone_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
