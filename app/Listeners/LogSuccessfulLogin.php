<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Support\UserAgentParser;
use Illuminate\Auth\Events\Login;
use Illuminate\Events\AsEventListener;
use Illuminate\Support\Facades\Request;

#[AsEventListener]
class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $userAgent = Request::userAgent();

        LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => Request::ip(),
            'user_agent' => $userAgent,
            'browser' => UserAgentParser::browser($userAgent),
            'platform' => UserAgentParser::platform($userAgent),
            'device_type' => UserAgentParser::deviceType($userAgent),
            'login_at' => now(),
            'status' => 'success',
            'email' => $user->email,
            // Capturés au moment de la connexion (snapshot) : un changement de
            // matricule/rôle ultérieur ne doit pas réécrire l'historique de sécurité.
            'matricule' => $user->matricule,
            'role' => $user->roles->first()?->name ?? $user->role,
        ]);
    }
}
