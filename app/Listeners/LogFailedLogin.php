<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

#[\Illuminate\Events\AsEventListener]
class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        LoginLog::create([
            'user_id' => null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'login_at' => now(),
            'status' => 'failed',
            'email' => $event->credentials['email'] ?? null,
        ]);
    }
}
