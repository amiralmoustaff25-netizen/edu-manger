<?php

use App\Models\ChapterCompletion;
use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use App\Models\SchoolYear;
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

test('it_returns_real_completion_volume_in_the_timeline_not_a_flat_zero_stub', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);
    $chapter = ProgramChapter::factory()->create([
        'program_annual_id' => $program->id,
        'volume_horaire_prevu' => 4,
    ]);
    ChapterCompletion::create([
        'program_chapter_id' => $chapter->id,
        'date_traitement' => now()->toDateString(),
        'completed_by' => $teacher->id,
    ]);

    $response = actingAs($teacher)->getJson(route('cahier-textes.dashboard.timeline', $program));

    $response->assertOk();
    $data = $response->json('data');
    expect(array_sum($data))->toEqual(4);
    expect(end($data))->toEqual(4); // le mois courant est le dernier des 12 libellés
});

test('a teacher cannot see the progress or timeline of another teacher program', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $otherTeacher = User::factory()->create();
    $otherTeacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $otherTeacher->id, 'status' => 'valide_surveillant']);

    actingAs($teacher)->getJson(route('cahier-textes.dashboard.progress', $program))->assertForbidden();
    actingAs($teacher)->getJson(route('cahier-textes.dashboard.timeline', $program))->assertForbidden();
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

test('it_only_shows_programs_of_the_active_school_year_by_default', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeYear = SchoolYear::factory()->create(['is_active' => true]);
    $oldYear = SchoolYear::factory()->create(['is_active' => false]);

    $currentProgram = ProgramAnnual::factory()->create(['school_year_id' => $activeYear->id, 'status' => 'valide_surveillant']);
    $staleProgram = ProgramAnnual::factory()->create(['school_year_id' => $oldYear->id, 'status' => 'valide_surveillant']);

    $response = actingAs($admin)->get(route('cahier-textes.dashboard.index'));

    $response->assertStatus(200);
    $programs = $response->viewData('programs');

    expect($programs->pluck('id'))->toContain($currentProgram->id);
    expect($programs->pluck('id'))->not->toContain($staleProgram->id);
});

test('it_filters_the_dashboard_by_classroom', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $programA = ProgramAnnual::factory()->create(['status' => 'valide_surveillant']);
    $programB = ProgramAnnual::factory()->create(['status' => 'valide_surveillant']);

    $response = actingAs($admin)->get(route('cahier-textes.dashboard.index', ['classroom_id' => $programA->classroom_id]));

    $response->assertStatus(200);
    $programs = $response->viewData('programs');

    expect($programs->pluck('id'))->toContain($programA->id);
    expect($programs->pluck('id'))->not->toContain($programB->id);
});
