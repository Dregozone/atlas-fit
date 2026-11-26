<?php

namespace App\Models;

use Database\Factories\UserDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's own choice of session for one weekday, replacing the programme's. A null session is a rest day.
 */
#[Fillable(['user_id', 'day', 'session'])]
class UserDay extends Model
{
    /** @use HasFactory<UserDayFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
