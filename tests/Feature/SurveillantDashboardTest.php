<?php

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'surveillant', 'guard_name' => 'web']);
    $this->seed(RoleAndPermissionSeeder::class);
    SchoolYear::create([
        'year_string' => '2026-2027',
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);
});

it('allows a surveillant to access the dashboard', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('surveillant');

    $response = $this->actingAs($user)->get(route('surveillant.dashboard'));

    $response->assertOk();
    $response->assertSee('Tableau de bord — Surveillant');
});

it('allows a surveillant to access the student attendances class list', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('surveillant');

    Classroom::create([
        'name' => '6ème A',
        'cycle' => 'college',
        'school_year_id' => SchoolYear::where('is_active', true)->first()->id,
    ]);

    $response = $this->actingAs($user)->get(route('surveillant.attendances.index'));

    $response->assertOk();
    $response->assertSee('6ème A');
});

it('allows a surveillant to access the teacher attendance page', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('surveillant');

    $response = $this->actingAs($user)->get(route('teacher-attendances.index'));

    $response->assertOk();
    $response->assertSee('Pointage des enseignants');
});
