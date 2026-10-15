<?php

namespace App\Listeners;

use App\Models\Login;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Database\Eloquent\Model;

class RecordLogin
{
    public function handle(LoginEvent $event): void
    {
        if ($event->user instanceof Model && $event->user->getKey() !== null) {
            Login::query()->create([
                'user_id' => $event->user->getKey(),
            ]);
        }
    }
}
