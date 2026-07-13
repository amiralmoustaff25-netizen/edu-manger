<?php

use App\Models\ChapterCompletion;
use App\Models\ProgramAnnual;
use App\Models\ProgramChapter;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('it_toggles_chapter_completion', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);
    $chapter = ProgramChapter::factory()->create(['program_annual_id' => $program->id]);

    $response = actingAs($teacher)->postJson(route('cahier-textes.toggle'), [
        'chapter_id' => $chapter->id,
        'date' => now()->toDateString(),
    ]);

    $response->assertJsonPath('toggled', true);
    $this->assertDatabaseHas('chapter_completions', ['program_chapter_id' => $chapter->id]);
});

test('it_undoes_toggle_on_second_click', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);
    $chapter = ProgramChapter::factory()->create(['program_annual_id' => $program->id]);

    actingAs($teacher)->postJson(route('cahier-textes.toggle'), ['chapter_id' => $chapter->id, 'date' => now()->toDateString()]);
    $response = actingAs($teacher)->postJson(route('cahier-textes.toggle'), ['chapter_id' => $chapter->id, 'date' => now()->toDateString()]);

    $response->assertJsonPath('toggled', false);
    $this->assertDatabaseMissing('chapter_completions', ['program_chapter_id' => $chapter->id]);
});

test('it_bulk_toggles_chapters', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);
    $chapters = ProgramChapter::factory()->count(2)->create(['program_annual_id' => $program->id]);

    $response = actingAs($teacher)->postJson(route('cahier-textes.bulk'), [
        'chapter_ids' => $chapters->pluck('id')->all(),
        'date' => now()->toDateString(),
    ]);

    $response->assertOk();
    $this->assertDatabaseCount('chapter_completions', 2);
});

test('it_marks_entire_lesson_as_done', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);
    $lesson = ProgramChapter::factory()->create(['program_annual_id' => $program->id, 'type' => 'lecon']);
    ProgramChapter::factory()->create(['program_annual_id' => $program->id, 'parent_id' => $lesson->id, 'type' => 'sous_partie']);

    $response = actingAs($teacher)->postJson(route('cahier-textes.mark-lesson'), [
        'lesson_id' => $lesson->id,
        'date' => now()->toDateString(),
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('chapter_completions', ['program_chapter_id' => $lesson->id]);
});

test('it_blocks_non_owner_teacher_from_toggling', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $otherTeacher = User::factory()->create();
    $otherTeacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);
    $chapter = ProgramChapter::factory()->create(['program_annual_id' => $program->id]);

    $response = actingAs($otherTeacher)->postJson(route('cahier-textes.toggle'), ['chapter_id' => $chapter->id, 'date' => now()->toDateString()]);

    $response->assertStatus(403);
});

test('it_autosaves_remark_via_api', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('professeur');
    $program = ProgramAnnual::factory()->create(['teacher_id' => $teacher->id, 'status' => 'valide_surveillant']);
    $chapter = ProgramChapter::factory()->create(['program_annual_id' => $program->id]);
    $completion = ChapterCompletion::factory()->create(['program_chapter_id' => $chapter->id]);

    $response = actingAs($teacher)->patchJson(route('cahier-textes.remark', $completion), ['remarque' => 'Très bien']);

    $response->assertOk();
    $this->assertSame('Très bien', $completion->fresh()->remarque);
});
