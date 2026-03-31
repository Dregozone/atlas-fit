<?php

namespace App\Models;

use Database\Factories\CompletedWorkoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'equipment', 'sets', 'reps', 'weight', 'is_deleted'])]
class CompletedWorkout extends Model
{
    /** @use HasFactory<CompletedWorkoutFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeBarbell($query)
    {
        return $query->where('equipment', 'like', '(Ben.) %');
    }
}
