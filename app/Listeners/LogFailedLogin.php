<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Support\UserAgentParser;
use Illuminate\Auth\Events\Failed;
use Illuminate\Events\AsEventListener;
use Illuminate\Support\Facades\Request;

#[AsEventListener]
class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $userAgent = Request::userAgent();
        $user = $event->user;

        LoginLog::create([
            'user_id' => $user?->id,
            'ip_address' => Request::ip(),
            'user_agent' => $userAgent,
            'browser' => UserAgentParser::browser($userAgent),
            'platform' => UserAgentParser::platform($userAgent),
            'device_type' => UserAgentParser::deviceType($userAgent),
            'login_at' => now(),
            'status' => 'failed',
            'email' => $event->credentials['email'] ?? null,
            'matricule' => $user?->matricule,
            'role' => $user?->roles->first()?->name ?? $user?->role,
        ]);
    }
}
