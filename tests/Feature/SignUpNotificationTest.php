<?php

use App\Http\Middleware\CaptureReferrer;
use App\Listeners\NotifyAdminOfSignUp;
use App\Mail\NewUserSignedUp;
use App\Mail\SignUpBurstSummary;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->withoutDefer();

    config(['mail.admin_notify_address' => 'admin@example.com']);
    RateLimiter::clear(NotifyAdminOfSignUp::RATE_LIMITER_KEY);
});

function registerUser(string $email, array $headers = []): TestResponse
{
    return test()->withHeaders($headers)->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
}

test('the admin is emailed when someone signs up', function () {
    Mail::fake();
    User::factory()->create();

    registerUser('jane@example.com', ['CF-IPCountry' => 'GB'])
        ->assertRedirect(route('dashboard', absolute: false));

    Mail::assertSent(NewUserSignedUp::class, function (NewUserSignedUp $mail): bool {
        return $mail->hasTo('admin@example.com')
            && $mail->hasSubject('New Atlas Fit sign-up: Jane Doe')
            && $mail->user->email === 'jane@example.com'
            && $mail->totalUsers === 2
            && $mail->country === 'GB';
    });
});

test('the email shows the sign-up details', function () {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $mail = new NewUserSignedUp($user, now()->setDate(2026, 7, 1)->setTime(12, 30), 42, 'https://google.com/', 'GB');

    $mail->assertSeeInHtml('Jane Doe')
        ->assertSeeInHtml('jane@example.com')
        ->assertSeeInHtml('1 Jul 2026, 13:30 BST')
        ->assertSeeInHtml('42')
        ->assertSeeInHtml('https://google.com/')
        ->assertSeeInHtml('GB');
});

test('the external referrer a guest arrived from is included', function () {
    Mail::fake();

    $this->get(route('register'), ['Referer' => 'https://www.reddit.com/r/fitness'])->assertOk();
    $this->get(route('register'), ['Referer' => url('/')])->assertOk();

    registerUser('jane@example.com');

    Mail::assertSent(NewUserSignedUp::class, fn (NewUserSignedUp $mail): bool => $mail->referrer === 'https://www.reddit.com/r/fitness');
});

test('internal referrers are not captured', function () {
    $this->get(route('register'), ['Referer' => url('/')])
        ->assertSessionMissing(CaptureReferrer::SESSION_KEY);
});

test('no email is sent when no admin address is set', function () {
    Mail::fake();
    config(['mail.admin_notify_address' => null]);

    registerUser('jane@example.com')->assertRedirect(route('dashboard', absolute: false));

    Mail::assertNothingSent();
});

test('a mail failure does not break registration', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Resend is down'));
    Log::spy();

    registerUser('jane@example.com')
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
    Log::shouldHaveReceived('error')->once();
});

test('a burst of sign-ups becomes one summary email', function () {
    Mail::fake();

    foreach (range(1, 8) as $number) {
        registerUser("user{$number}@example.com");
        auth()->logout();
    }

    Mail::assertSent(NewUserSignedUp::class, 5);
    Mail::assertSent(SignUpBurstSummary::class, 1);
    Mail::assertSent(SignUpBurstSummary::class, fn (SignUpBurstSummary $mail): bool => $mail->recentUsers->count() === 6
        && $mail->hasSubject('Atlas Fit sign-up burst: 6 sign-ups in 10 minutes'));
});

test('individual emails resume after the burst window', function () {
    Mail::fake();

    foreach (range(1, 6) as $number) {
        registerUser("user{$number}@example.com");
        auth()->logout();
    }

    $this->travel(NotifyAdminOfSignUp::BURST_WINDOW_MINUTES + 1)->minutes();

    registerUser('later@example.com');

    Mail::assertSent(NewUserSignedUp::class, 6);
    Mail::assertSent(NewUserSignedUp::class, fn (NewUserSignedUp $mail): bool => $mail->user->email === 'later@example.com');
});
