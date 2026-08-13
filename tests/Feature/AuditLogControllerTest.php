<?php

use App\Models\AuditLog;
use App\Models\User;

test('admin can view the audit log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'updated',
        'model_type' => User::class,
        'model_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
    ]);

    $response = $this->actingAs($admin)->get('/audit-logs');

    $response->assertOk();
});

test('comptable cannot access the audit log', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    $response = $this->actingAs($comptable)->get('/audit-logs');

    $response->assertForbidden();
});

test('the audit log list links to the details page for each entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $log = AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'updated',
        'model_type' => User::class,
        'model_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
    ]);

    $response = $this->actingAs($admin)->get('/audit-logs');

    $response->assertOk()->assertSee(route('audit-logs.show', $log), false);
});

test('admin can view a single audit log entry with old and new values', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $log = AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'updated',
        'model_type' => User::class,
        'model_id' => $admin->id,
        'old_values' => ['is_active' => false],
        'new_values' => ['is_active' => true],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
    ]);

    $response = $this->actingAs($admin)->get("/audit-logs/{$log->id}");

    $response->assertOk()
        ->assertSee('is_active')
        ->assertSee('User');
});

test('filtering the audit log by action and user narrows the results', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $otherUser = User::factory()->create();

    AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'created',
        'model_type' => User::class,
        'model_id' => $otherUser->id,
    ]);
    AuditLog::create([
        'user_id' => $otherUser->id,
        'action' => 'archived',
        'model_type' => User::class,
        'model_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($admin)->get('/audit-logs?'.http_build_query([
        'user_id' => $admin->id,
        'action' => 'created',
    ]));

    $response->assertOk()->assertViewHas('logs', function ($logs) {
        return $logs->total() === 1 && $logs->first()->action === 'created';
    });
});
