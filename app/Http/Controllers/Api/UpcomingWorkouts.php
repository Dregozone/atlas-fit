<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Day;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSession;
use Carbon\CarbonInterface;

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
        $day = Day::where('day', $dayName)->first();

        if (! $day || empty($day->session)) {
            return response()->json([
                'message' => 'No workout scheduled for today',
                'nextWorkout' => $this->resolveNextWorkout($date),
            ], 200)->header('Cache-Control', 'no-store');
        }

        $session = WorkoutSession::where('session', $day->session)->first();
        $primaryExercises = $session
            ? Workout::where('session', $session->primary_muscle_group)->orderBy('exercise_no')->get()
            : collect();
        $secondaryExercises = $session
            ? Workout::where('session', $session->secondary_muscle_group)->orderBy('exercise_no')->get()
            : collect();

        $upcomingWorkouts = $primaryExercises->concat($secondaryExercises)->values();

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
    private function resolveNextWorkout(CarbonInterface $from): ?array
    {
        for ($i = 1; $i <= 7; $i++) {
            $nextDate = $from->copy()->addDays($i);
            $nextDay = Day::where('day', $nextDate->format('l'))->first();

            if (! $nextDay || empty($nextDay->session)) {
                continue;
            }

            $nextRotationWeek = ((int) $nextDate->format('W') % 3) + 1;
            $nextSession = WorkoutSession::where('session', $nextDay->session)->first();

            $primary = $nextSession
                ? Workout::where('session', $nextSession->primary_muscle_group)->orderBy('exercise_no')->get()
                : collect();
            $secondary = $nextSession
                ? Workout::where('session', $nextSession->secondary_muscle_group)->orderBy('exercise_no')->get()
                : collect();

            return [
                'date' => $nextDate->format('Y-m-d'),
                'day' => $nextDate->format('l'),
                'rotation' => Rotation::where('week', $nextRotationWeek)->first(),
                'upcomingWorkouts' => $primary->concat($secondary)->values(),
            ];
        }

        return null;
    }
}
