<?php

use App\Models\LoginLog;
use App\Models\User;

test('admin can view login logs', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    LoginLog::create([
        'user_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'login_at' => now(),
        'status' => 'success',
        'email' => $admin->email,
    ]);

    $response = $this->actingAs($admin)
        ->get('/login-logs');

    $response->assertOk();
});

test('comptable cannot access login logs', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    $response = $this->actingAs($comptable)
        ->get('/login-logs');

    $response->assertForbidden();
});

test('admin can view single login log details', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $log = LoginLog::create([
        'user_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'login_at' => now(),
        'status' => 'success',
        'email' => $admin->email,
    ]);

    $response = $this->actingAs($admin)
        ->get("/login-logs/{$log->id}");

    $response->assertOk();
});
