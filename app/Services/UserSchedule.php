<?php

namespace App\Services;

use App\Models\Day;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSession;
use Illuminate\Support\Collection;

/**
 * The shared programme as one user sees it, with their own changes applied: weekdays they
 * have moved to another session or a rest day, and exercises they have switched off.
 */
class UserSchedule
{
    public const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /** @var Collection<string, ?string>|null */
    private ?Collection $customDays = null;

    /** @var Collection<string, ?string>|null */
    private ?Collection $programmeDays = null;

    /** @var array<int, int>|null */
    private ?array $excludedWorkoutIds = null;

    public function __construct(public User $user) {}

    /**
     * The session key the user trains on the given weekday, or null for a rest day.
     */
    public function sessionKeyFor(string $dayName): ?string
    {
        if ($this->customDays()->has($dayName)) {
            return $this->customDays()->get($dayName);
        }

        return $this->programmeSessionKeyFor($dayName);
    }

    /**
     * The session key the shared programme sets for the given weekday, or null for a rest day.
     */
    public function programmeSessionKeyFor(string $dayName): ?string
    {
        return $this->programmeDays()->get($dayName) ?: null;
    }

    public function hasCustomDay(string $dayName): bool
    {
        return $this->customDays()->has($dayName);
    }

    /**
     * The user's session for the given weekday, or null on a rest day.
     */
    public function sessionFor(string $dayName): ?WorkoutSession
    {
        $sessionKey = $this->sessionKeyFor($dayName);

        return $sessionKey ? WorkoutSession::where('session', $sessionKey)->first() : null;
    }

    /**
     * The exercises the user has left switched on for a muscle group, in programme order.
     *
     * @return Collection<int, Workout>
     */
    public function includedWorkoutsFor(string $muscleGroup): Collection
    {
        return Workout::where('session', $muscleGroup)
            ->whereNotIn('id', $this->excludedWorkoutIds())
            ->orderBy('exercise_no')
            ->get();
    }

    public function isExcluded(Workout $workout): bool
    {
        return in_array($workout->id, $this->excludedWorkoutIds(), true);
    }

    /**
     * @return array<int, int>
     */
    public function excludedWorkoutIds(): array
    {
        return $this->excludedWorkoutIds ??= $this->user->excludedWorkouts()
            ->pluck('workouts.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function hasCustomisations(): bool
    {
        return $this->customDays()->isNotEmpty() || $this->excludedWorkoutIds() !== [];
    }

    /**
     * @return Collection<string, ?string>
     */
    private function customDays(): Collection
    {
        return $this->customDays ??= $this->user->scheduleDays()->pluck('session', 'day');
    }

    /**
     * @return Collection<string, ?string>
     */
    private function programmeDays(): Collection
    {
        return $this->programmeDays ??= Day::pluck('session', 'day');
    }
}
