<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\ProgramAnnual;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function createProgramContext(): array
{
    $schoolYear = SchoolYear::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $subject = Matiere::factory()->create();
    $teacherUser = User::factory()->create();
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
    $assignment = PedagogicalAssignment::create(['teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => $subject->id, 'school_year_id' => $schoolYear->id, 'volume_horaire_hebdo' => 4, 'is_active' => true]);

    return compact('schoolYear', 'classroom', 'subject', 'teacherUser', 'teacher', 'assignment');
}

test('it_creates_program_with_chapters_hierarchy', function () {
    $context = createProgramContext();
    $schoolYear = $context['schoolYear'];
    $classroom = $context['classroom'];
    $subject = $context['subject'];
    $teacherUser = $context['teacherUser'];

    $response = actingAs($teacherUser)->post(route('programs.store'), [
        'pedagogical_assignment_id' => $context['assignment']->id,
        'chapters' => [
            ['type' => 'chapitre', 'titre' => 'Chapitre 1', 'description' => 'Intro', 'volume_horaire_prevu' => 2.5, 'children' => [
                ['type' => 'lecon', 'titre' => 'Leçon 1', 'description' => 'Leçon', 'volume_horaire_prevu' => 1.5, 'children' => [
                    ['type' => 'sous_partie', 'titre' => 'Sous-partie', 'description' => 'SP', 'volume_horaire_prevu' => 0.5],
                ]],
            ]],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('program_annuals', ['classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
    $this->assertDatabaseCount('program_chapters', 3);
});

test('it_validates_chapter_max_depth_3_levels', function () {
    $context = createProgramContext();
    $teacherUser = $context['teacherUser'];

    $response = actingAs($teacherUser)->post(route('programs.store'), [
        'pedagogical_assignment_id' => $context['assignment']->id,
        'chapters' => [
            ['type' => 'chapitre', 'titre' => 'Chapitre 1', 'description' => 'Intro', 'volume_horaire_prevu' => 2.5, 'children' => [
                ['type' => 'lecon', 'titre' => 'Leçon 1', 'description' => 'Leçon', 'volume_horaire_prevu' => 1.5, 'children' => [
                    ['type' => 'sous_partie', 'titre' => 'Sous-partie', 'description' => 'SP', 'volume_horaire_prevu' => 0.5, 'children' => [
                        ['type' => 'sous_partie', 'titre' => 'Trop profond', 'description' => 'SP2', 'volume_horaire_prevu' => 0.1],
                    ]],
                ]],
            ]],
        ],
    ]);

    $response->assertSessionHasErrors('chapters');
});

test('it_submits_program_to_surveillant', function () {
    $context = createProgramContext();
    $program = ProgramAnnual::factory()->create([
        'teacher_id' => $context['teacherUser']->id,
        'classroom_id' => $context['classroom']->id,
        'subject_id' => $context['subject']->id,
        'school_year_id' => $context['schoolYear']->id,
        'status' => 'brouillon',
    ]);

    $response = actingAs($context['teacherUser'])->post(route('programs.submit', $program));

    $response->assertRedirect();
    $program->refresh();
    expect($program->status)->toBe('soumis');
});

test('it_validates_transitions_surveillant_then_directeur', function () {
    $context = createProgramContext();
    $surveillant = User::factory()->create();
    $surveillant->assignRole('surveillant');
    $directeur = User::factory()->create();
    $directeur->assignRole('admin');

    $program = ProgramAnnual::factory()->create([
        'teacher_id' => $context['teacherUser']->id,
        'classroom_id' => $context['classroom']->id,
        'subject_id' => $context['subject']->id,
        'school_year_id' => $context['schoolYear']->id,
        'status' => 'soumis',
    ]);

    actingAs($surveillant)->post(route('programs.validate-surveillant', $program));
    $program->refresh();
    expect($program->status)->toBe('valide_surveillant');

    actingAs($directeur)->post(route('programs.validate-directeur', $program));
    $program->refresh();
    expect($program->status)->toBe('valide_directeur');
});

test('it_blocks_invalid_transitions', function () {
    $context = createProgramContext();
    $surveillant = User::factory()->create();
    $surveillant->assignRole('surveillant');

    $program = ProgramAnnual::factory()->create([
        'teacher_id' => $context['teacherUser']->id,
        'classroom_id' => $context['classroom']->id,
        'subject_id' => $context['subject']->id,
        'school_year_id' => $context['schoolYear']->id,
        'status' => 'brouillon',
    ]);

    $response = actingAs($surveillant)->post(route('programs.validate-surveillant', $program));

    $response->assertStatus(403);
});

test('it_rejects_program_with_mandatory_motif', function () {
    $context = createProgramContext();
    $surveillant = User::factory()->create();
    $surveillant->assignRole('surveillant');

    $program = ProgramAnnual::factory()->create([
        'teacher_id' => $context['teacherUser']->id,
        'classroom_id' => $context['classroom']->id,
        'subject_id' => $context['subject']->id,
        'school_year_id' => $context['schoolYear']->id,
        'status' => 'soumis',
    ]);

    $response = actingAs($surveillant)->post(route('programs.reject', $program), ['motif' => 'ok']);

    $response->assertSessionHasErrors('motif');
});
