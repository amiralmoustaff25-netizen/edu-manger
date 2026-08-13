<?php

use App\Models\LoginLog;
use App\Models\User;

test('a successful login captures matricule, role and parsed device info', function () {
    $user = User::factory()->create(['matricule' => 'ADM-26-0001']);
    $user->assignRole('admin');

    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36'])
        ->post('/login', ['email' => $user->email, 'password' => 'password']);

    $log = LoginLog::where('user_id', $user->id)->latest('login_at')->first();

    expect($log)->not->toBeNull();
    expect($log->matricule)->toBe('ADM-26-0001');
    expect($log->role)->toBe('admin');
    expect($log->browser)->toBe('Chrome');
    expect($log->platform)->toBe('Windows');
    expect($log->device_type)->toBe('ordinateur');
});

test('a failed login attempt for a real email still captures matricule and role', function () {
    $user = User::factory()->create(['matricule' => 'PROF-26-0001']);
    $user->assignRole('professeur');

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

    $log = LoginLog::where('email', $user->email)->where('status', 'failed')->latest('login_at')->first();

    expect($log)->not->toBeNull();
    expect($log->matricule)->toBe('PROF-26-0001');
    expect($log->role)->toBe('professeur');
});

test('logging out sets logout_at on the most recent open login log entry', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $log = LoginLog::where('user_id', $user->id)->latest('login_at')->first();
    expect($log->logout_at)->toBeNull();

    $this->post('/logout');

    expect($log->refresh()->logout_at)->not->toBeNull();
    expect($log->duration_in_seconds)->not->toBeNull();
});
