<?php

namespace App\Models;

use Database\Factories\ConsumedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'meal_item_id', 'quantity'])]
class Consumed extends Model
{
    /** @use HasFactory<ConsumedFactory> */
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mealItem()
    {
        return $this->belongsTo(MealItem::class);
    }
}
