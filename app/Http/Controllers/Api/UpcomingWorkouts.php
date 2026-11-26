<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Workout;
use App\Services\UserSchedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UpcomingWorkouts extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $identifier, string $token)
    {
        // Check that both are present to enforce users that have opted out of API access to not have their data returned.
        if (empty($identifier) || empty($token)) {
            return response()->json([
                'message' => 'Missing identifier or token',
            ], 400)->header('Cache-Control', 'no-store');
        }

        // Check that a user exists with the provided identifier and token
        $user = User::query()
            ->where([
                ['api_identifier', $identifier],
                ['api_token', $token],
            ])
            ->first();
        if (! $user) {
            return response()->json([
                'message' => 'Invalid identifier or token',
            ], 401)->header('Cache-Control', 'no-store');
        }

        // At this point the user is authenticated, we can return the upcoming scheduled workouts for this user for today.
        $date = now();

        $dayName = $date->format('l');
        $weekNumber = (int) $date->format('W');
        $rotationWeek = ($weekNumber % 3) + 1;

        $rotation = Rotation::where('week', $rotationWeek)->first();
        $schedule = new UserSchedule($user);

        if (! $schedule->sessionKeyFor($dayName)) {
            return response()->json([
                'message' => 'No workout scheduled for today',
                'nextWorkout' => $this->resolveNextWorkout($schedule, $date),
            ], 200)->header('Cache-Control', 'no-store');
        }

        $upcomingWorkouts = $this->workoutsFor($schedule, $dayName);

        return response()->json([
            'rotation' => $rotation,
            'upcomingWorkouts' => $upcomingWorkouts,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Find the next scheduled workout day within 7 days from the given date.
     *
     * @return array<string, mixed>|null
     */
    private function resolveNextWorkout(UserSchedule $schedule, CarbonInterface $from): ?array
    {
        for ($i = 1; $i <= 7; $i++) {
            $nextDate = $from->copy()->addDays($i);

            if (! $schedule->sessionKeyFor($nextDate->format('l'))) {
                continue;
            }

            $nextRotationWeek = ((int) $nextDate->format('W') % 3) + 1;

            return [
                'date' => $nextDate->format('Y-m-d'),
                'day' => $nextDate->format('l'),
                'rotation' => Rotation::where('week', $nextRotationWeek)->first(),
                'upcomingWorkouts' => $this->workoutsFor($schedule, $nextDate->format('l')),
            ];
        }

        return null;
    }

    /**
     * The exercises the user has switched on for their session on the given weekday.
     *
     * @return Collection<int, Workout>
     */
    private function workoutsFor(UserSchedule $schedule, string $dayName): Collection
    {
        $session = $schedule->sessionFor($dayName);

        if (! $session) {
            return collect();
        }

        return $schedule->includedWorkoutsFor($session->primary_muscle_group)
            ->concat($schedule->includedWorkoutsFor($session->secondary_muscle_group))
            ->values();
    }
}
