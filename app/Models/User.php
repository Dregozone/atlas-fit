<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password', 'body_weight_lbs', 'fitness_goal', 'is_admin'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function completedWorkouts()
    {
        return $this->hasMany(CompletedWorkout::class);
    }

    public function consumeds()
    {
        return $this->hasMany(Consumed::class);
    }

    public function bodyWeights()
    {
        return $this->hasMany(BodyWeight::class);
    }

    public function bodyWeightGoal()
    {
        return $this->hasOne(BodyWeightGoal::class);
    }

    /**
     * Programme exercises the user has switched off (for example, ones needing gym equipment they don't have).
     */
    public function excludedWorkouts(): BelongsToMany
    {
        return $this->belongsToMany(Workout::class, 'excluded_workouts')->withTimestamps();
    }

    /**
     * Weekdays where the user trains a different session, or rests, instead of following the programme.
     */
    public function scheduleDays(): HasMany
    {
        return $this->hasMany(UserDay::class);
    }
}
