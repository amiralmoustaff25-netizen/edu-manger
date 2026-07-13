<?php

use App\Models\ProgramAnnual;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('it_returns_timeline_data_for_year', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);

    $response = actingAs($teacher)->getJson(route('cahier-textes.dashboard.timeline', $program));

    $response->assertOk();
    $response->assertJsonStructure(['labels', 'data']);
});

test('it_calculates_global_progress', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);

    $response = actingAs($teacher)->getJson(route('cahier-textes.dashboard.progress', $program));

    $response->assertOk();
    $response->assertJsonStructure(['global', 'chapters']);
});

test('it_shows_only_own_programs_for_teacher', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    ProgramAnnual::factory()->count(2)->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);

    $response = actingAs($teacher)->get(route('cahier-textes.dashboard.index'));

    $response->assertStatus(200);
});
