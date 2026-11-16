<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Events\Dispatcher;

class AuthEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, $this->handleLogin(...));
        $events->listen(Failed::class, $this->handleFailed(...));
    }

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->save();
        app(ActivityLogger::class)->record('auth.login', $event->user);
    }

    public function handleFailed(Failed $event): void
    {
        app(ActivityLogger::class)->record(
            'auth.failed',
            $event->user instanceof User ? $event->user : null,
        );
    }
}
