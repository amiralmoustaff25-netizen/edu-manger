<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * Régression : un professeur "titulaire" d'une classe de primaire (Classroom.teacher_id)
 * sans PedagogicalAssignment explicite — cas réel du matricule PROF-26-0006, où le
 * titulariat avait été renseigné hors de l'écran "Configuration pédagogique" qui
 * synchronise normalement ces affectations (ClassroomController::syncPrimaryTeacherAssignments).
 */
function createTitulaireFixture(): array
{
    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    Teacher::factory()->create(['user_id' => $teacherUser->id]);

    $closedYear = SchoolYear::factory()->create(['is_active' => false, 'status' => 'closed']);
    $activeYear = SchoolYear::factory()->create(['is_active' => true, 'status' => 'active']);

    $oldClassroom = Classroom::factory()->create([
        'cycle' => 'primaire',
        'school_year_id' => $closedYear->id,
        'teacher_id' => $teacherUser->id,
    ]);
    $currentClassroom = Classroom::factory()->create([
        'cycle' => 'primaire',
        'school_year_id' => $activeYear->id,
        'teacher_id' => $teacherUser->id,
    ]);

    return [$teacherUser, $oldClassroom, $currentClassroom];
}

test('mes classes only shows the titulaire classroom of the active school year', function () {
    [$teacherUser, $oldClassroom, $currentClassroom] = createTitulaireFixture();

    $response = actingAs($teacherUser)->get(route('professeur.classes.index'));

    $response->assertOk()
        ->assertSee($currentClassroom->name)
        ->assertDontSee($oldClassroom->name);
});

test('grade entry offers the current titulaire classroom and its general subjects without an explicit assignment', function () {
    [$teacherUser, , $currentClassroom] = createTitulaireFixture();
    $matiere = Matiere::factory()->create(['nom' => 'Mathématiques']);

    $response = actingAs($teacherUser)->get(route('professeur.notes.index', ['classroom_id' => $currentClassroom->id]));

    $response->assertOk()
        ->assertSee($currentClassroom->name)
        ->assertSee($matiere->nom);
});

test('a specialist primary subject is not offered to the titulaire without an explicit assignment', function () {
    [$teacherUser, , $currentClassroom] = createTitulaireFixture();
    Matiere::factory()->create(['nom' => 'Anglais']);

    $response = actingAs($teacherUser)->get(route('professeur.notes.index', ['classroom_id' => $currentClassroom->id]));

    $response->assertOk()->assertDontSee('Anglais');
});

test('teacher can save grades for a general subject as titulaire without an explicit assignment', function () {
    [$teacherUser, , $currentClassroom] = createTitulaireFixture();
    $matiere = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $student = User::factory()->create(['role' => 'eleve']);
    Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $currentClassroom->id,
        'school_year_id' => $currentClassroom->school_year_id,
        'status' => 'active',
    ]);

    $response = actingAs($teacherUser)->post(route('professeur.notes.store'), [
        'classroom_id' => $currentClassroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 15],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('notes', [
        'user_id' => $student->id,
        'classroom_id' => $currentClassroom->id,
        'matiere_id' => $matiere->id,
        'valeur' => 15,
    ]);
});

test('cahier de texte selector only lists the classrooms and subjects assigned to the teacher', function () {
    [$teacherUser, , $currentClassroom] = createTitulaireFixture();
    $generalMatiere = Matiere::factory()->create(['nom' => 'Français']);

    $otherTeacherUser = User::factory()->create(['role' => 'professeur']);
    $otherTeacherUser->assignRole('professeur');
    $otherClassroom = Classroom::factory()->create([
        'cycle' => 'college',
        'school_year_id' => $currentClassroom->school_year_id,
        'teacher_id' => $otherTeacherUser->id,
    ]);

    $response = actingAs($teacherUser)->get(route('cahier-textes.select'));

    $response->assertOk()
        ->assertSee($currentClassroom->name)
        ->assertSee($generalMatiere->nom)
        ->assertDontSee($otherClassroom->name);
});
