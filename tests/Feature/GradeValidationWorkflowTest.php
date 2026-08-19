<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;

function createGradeFixture(): array
{
    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true, 'status' => 'active']);
    // Cycle "college" : seul cycle où le type d'évaluation "devoir" est autorisé
    // (EvaluationTypeScope) — le primaire n'évalue qu'en "composition".
    $classroom = Classroom::create(['name' => '6eme A', 'school_year_id' => $schoolYear->id, 'cycle' => 'college']);
    $matiere = Matiere::factory()->create();

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
    // PedagogicalAssignment (écran "Affectations pédagogiques") : seule source de vérité
    // des affectations enseignant/classe/matière alimentée par l'administration — le
    // pivot teacher_classroom (Teacher::classrooms()) utilisé ici auparavant n'est plus
    // jamais renseigné par aucun écran accessible (voir GradeController::index/store).
    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $student = User::factory()->create(['role' => 'eleve']);
    Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'active',
    ]);

    $note = Note::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'valeur' => 14,
        'type_evaluation' => 'devoir',
        'periode' => 'trimestre_1',
    ]);

    return [$teacherUser, $classroom, $matiere, $student, $note];
}

test('teacher can edit grades before validation', function () {
    [$teacherUser, $classroom, $matiere, $student] = createGradeFixture();

    $response = $this->actingAs($teacherUser)->post('/professeur/notes', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'devoir',
        'periode' => 'trimestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 16, 'appreciation' => 'Bien'],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('notes', ['user_id' => $student->id, 'valeur' => 16]);
});

test('admin can validate grades which locks them for the teacher', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    [$teacherUser, $classroom, $matiere, $student, $note] = createGradeFixture();

    $response = $this->actingAs($admin)->post('/notes/validate', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'devoir',
        'periode' => 'trimestre_1',
    ]);

    $response->assertRedirect();
    expect($note->refresh()->isValidated())->toBeTrue();

    $editAttempt = $this->actingAs($teacherUser)->post('/professeur/notes', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'devoir',
        'periode' => 'trimestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 18, 'appreciation' => 'Excellent'],
        ],
    ]);

    $editAttempt->assertSessionHas('error');
    $this->assertDatabaseHas('notes', ['id' => $note->id, 'valeur' => 14]);
});

test('admin cannot reopen validated grades, only super-admin can', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    [, $classroom, $matiere, , $note] = createGradeFixture();
    $note->validate($admin->id);

    $adminAttempt = $this->actingAs($admin)->post('/notes/reopen', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'devoir',
        'periode' => 'trimestre_1',
    ]);
    $adminAttempt->assertForbidden();
    expect($note->refresh()->isValidated())->toBeTrue();

    $superAdminAttempt = $this->actingAs($superAdmin)->post('/notes/reopen', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'devoir',
        'periode' => 'trimestre_1',
    ]);
    $superAdminAttempt->assertRedirect();
    expect($note->refresh()->isValidated())->toBeFalse();
});
