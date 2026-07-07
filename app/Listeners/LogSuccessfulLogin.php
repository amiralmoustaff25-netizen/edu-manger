<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

#[\Illuminate\Events\AsEventListener]
class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'login_at' => now(),
            'status' => 'success',
            'email' => $user->email,
        ]);
    }
}
