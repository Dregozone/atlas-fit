<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferrer
{
    public const string SESSION_KEY = 'sign_up_referrer';

    /**
     * Remember the first off-site referrer a guest arrives from, so it can be reported on sign-up.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $referrer = $request->headers->get('referer');

        if (
            $referrer !== null
            && $request->user() === null
            && $request->hasSession()
            && ! $request->session()->has(self::SESSION_KEY)
            && parse_url($referrer, PHP_URL_HOST) !== $request->getHost()
        ) {
            $request->session()->put(self::SESSION_KEY, Str::limit($referrer, 255, ''));
        }

        return $next($request);
    }
}
