<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $now = now();

            DB::table('users')
                ->where('id', $user->getAuthIdentifier())
                ->where(function ($query) use ($now): void {
                    $query->whereNull('last_active_at')
                        ->orWhere('last_active_at', '<=', $now->copy()->subHour());
                })
                ->update(['last_active_at' => $now]);
        }

        return $next($request);
    }
}
