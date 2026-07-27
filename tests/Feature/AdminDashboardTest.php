<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin_can_access_admin_dashboard_with_allowed_modules', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');

    $response = actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Administration');
    $response->assertSee('Scolarité');
    $response->assertSee('Finance');
});

test('super_admin_sees_admin_dashboard_with_super_admin_badge', function () {
    $superAdmin = User::factory()->create(['is_active' => true]);
    $superAdmin->assignRole('super-admin');

    $response = actingAs($superAdmin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Super Admin');
});

test('teacher_cannot_access_admin_dashboard', function () {
    $teacher = User::factory()->create(['is_active' => true]);
    $teacher->assignRole('professeur');

    actingAs($teacher)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
