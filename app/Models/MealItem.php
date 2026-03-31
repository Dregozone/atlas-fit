<?php

namespace App\Models;

use Database\Factories\MealItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['img', 'name', 'carbs', 'protein', 'fat', 'calories', 'is_active'])]
class MealItem extends Model
{
    /** @use HasFactory<MealItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
