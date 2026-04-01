<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consumed;
use App\Models\User;
use App\Services\MacroCalculator;
use Carbon\Carbon;

class Macros extends Controller
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
            ], 400);
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
            ], 401);
        }

        // Find consumed today
        $consumedToday = Consumed::query()
            ->join('meal_items', 'consumeds.meal_item_id', '=', 'meal_items.id')
            ->where('consumeds.user_id', $user->id)
            ->whereDate('consumeds.created_at', Carbon::today())
            ->selectRaw('
                COALESCE(SUM(consumeds.quantity * meal_items.carbs), 0) AS carbs,
                COALESCE(SUM(consumeds.quantity * meal_items.protein), 0) AS protein,
                COALESCE(SUM(consumeds.quantity * meal_items.fat), 0) AS fat,
                COALESCE(SUM(consumeds.quantity * meal_items.calories), 0) AS calories
            ')
            ->first()
            ->getAttributes();

        // Find daily goals
        if (! $user->body_weight_lbs || ! $user->fitness_goal) {
            return ['carbs' => null, 'protein' => null, 'fat' => null, 'calories' => null];
        }

        $calc = new MacroCalculator;
        $calc->setWeightLbs($user->body_weight_lbs);
        $calc->setGoal($user->fitness_goal);
        $calc->findMacros();

        $macroGoals = [
            'carbs' => round($calc->getCarbs()),
            'protein' => round($calc->getProtein()),
            'fat' => round($calc->getFat()),
            'calories' => round($calc->getCalories()),
        ];

        return response()->json([
            'consumedToday' => $consumedToday,
            'macroGoals' => $macroGoals,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
