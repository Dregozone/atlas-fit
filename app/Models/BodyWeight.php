<?php

namespace App\Models;

use Database\Factories\BodyWeightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'weight_in_lbs'])]
class BodyWeight extends Model
{
    /** @use HasFactory<BodyWeightFactory> */
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
