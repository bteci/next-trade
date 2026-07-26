<?php

namespace App\Listeners;

use App\Services\UserActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;

class LogAuthActivity
{
    public function __construct(private UserActivityLogService $activityLog) {}

    public function handleLogin(Login $event): void
    {
        $event->user->forceFill(['last_login_at' => now()])->save();
        $this->activityLog->log($event->user, 'login', 'Logged in');
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->activityLog->log($event->user, 'logout', 'Logged out');
        }
    }

    public function handleRegistered(Registered $event): void
    {
        $this->activityLog->log($event->user, 'registered', 'Account created');
    }
}
