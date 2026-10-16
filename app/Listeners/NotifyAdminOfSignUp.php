<?php

namespace App\Listeners;

use App\Http\Middleware\CaptureReferrer;
use App\Mail\NewUserSignedUp;
use App\Mail\SignUpBurstSummary;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class NotifyAdminOfSignUp
{
    public const int BURST_LIMIT = 5;

    public const int BURST_WINDOW_MINUTES = 10;

    public const string RATE_LIMITER_KEY = 'admin-sign-up-notifications';

    public function handle(Registered $event): void
    {
        $adminAddress = config('mail.admin_notify_address');

        if (blank($adminAddress) || ! $event->user instanceof User) {
            return;
        }

        $user = $event->user;
        $signedUpAt = now();
        $referrer = request()->hasSession() ? request()->session()->get(CaptureReferrer::SESSION_KEY) : null;
        $country = request()->header('CF-IPCountry');

        defer(function () use ($adminAddress, $user, $signedUpAt, $referrer, $country): void {
            try {
                $this->notify($adminAddress, $user, $signedUpAt, $referrer, $country);
            } catch (Throwable $exception) {
                Log::error('Failed to send sign-up notification email.', [
                    'user_id' => $user->getKey(),
                    'exception' => $exception,
                ]);
            }
        });
    }

    /**
     * Send the individual email, or one burst summary once the limit is passed within the window.
     */
    protected function notify(string $adminAddress, User $user, CarbonImmutable $signedUpAt, ?string $referrer, ?string $country): void
    {
        $signUpsInWindow = RateLimiter::hit(self::RATE_LIMITER_KEY, self::BURST_WINDOW_MINUTES * 60);

        if ($signUpsInWindow <= self::BURST_LIMIT) {
            Mail::to($adminAddress)->send(new NewUserSignedUp(
                $user,
                $signedUpAt,
                User::query()->count(),
                $referrer,
                $country,
            ));

            return;
        }

        if ($signUpsInWindow === self::BURST_LIMIT + 1) {
            Mail::to($adminAddress)->send(new SignUpBurstSummary(
                User::query()
                    ->where('created_at', '>=', now()->subMinutes(self::BURST_WINDOW_MINUTES))
                    ->oldest()
                    ->get(),
                self::BURST_WINDOW_MINUTES,
                User::query()->count(),
            ));
        }
    }
}
