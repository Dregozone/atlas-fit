<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyStats extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = config('stats.token');
        $providedToken = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            abort(404);
        }

        if (! is_string($providedToken) || ! hash_equals($token, $providedToken)) {
            abort(401);
        }

        $validated = $request->validate([
            'date' => ['sometimes', 'required', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? CarbonImmutable::now('Europe/London')->subDay()->toDateString();
        $start = CarbonImmutable::parse($date, 'Europe/London')->startOfDay();
        $end = $start->addDay();
        $startUtc = $start->utc()->toDateTimeString();
        $endUtc = $end->utc()->toDateTimeString();

        return response()->json([
            'app' => 'Atlas Fit',
            'date' => $date,
            'signups' => DB::table('users')
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<', $endUtc)
                ->count(),
            'logins' => [
                'total' => DB::table('logins')
                    ->where('created_at', '>=', $startUtc)
                    ->where('created_at', '<', $endUtc)
                    ->count(),
                'unique' => DB::table('logins')
                    ->where('created_at', '>=', $startUtc)
                    ->where('created_at', '<', $endUtc)
                    ->distinct('user_id')
                    ->count('user_id'),
            ],
            'users' => [
                'total' => DB::table('users')->count(),
                'active_7d' => $this->activeUsers($end, 7),
                'active_30d' => $this->activeUsers($end, 30),
            ],
        ])->header('Cache-Control', 'no-store');
    }

    private function activeUsers(CarbonImmutable $end, int $days): int
    {
        return DB::table('users')
            ->where('last_active_at', '>=', $end->subDays($days)->utc()->toDateTimeString())
            ->where('last_active_at', '<', $end->utc()->toDateTimeString())
            ->count();
    }
}
