<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;

function createGradeFixture(): array
{
    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true, 'status' => 'active']);
    $classroom = Classroom::create(['name' => 'CM2 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $matiere = Matiere::factory()->create();

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
    $teacher->classrooms()->attach($classroom->id, [
        'annee_scolaire' => $schoolYear->year_string,
        'matiere_id' => $matiere->id,
        'volume_horaire_hebdo' => 4,
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
        'type_evaluation' => 'Devoir',
        'periode' => 'trimestre_1',
    ]);

    return [$teacherUser, $classroom, $matiere, $student, $note];
}

test('teacher can edit grades before validation', function () {
    [$teacherUser, $classroom, $matiere, $student] = createGradeFixture();

    $response = $this->actingAs($teacherUser)->post('/professeur/notes', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'Devoir',
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
        'type_evaluation' => 'Devoir',
        'periode' => 'trimestre_1',
    ]);

    $response->assertRedirect();
    expect($note->refresh()->isValidated())->toBeTrue();

    $editAttempt = $this->actingAs($teacherUser)->post('/professeur/notes', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'Devoir',
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
        'type_evaluation' => 'Devoir',
        'periode' => 'trimestre_1',
    ]);
    $adminAttempt->assertForbidden();
    expect($note->refresh()->isValidated())->toBeTrue();

    $superAdminAttempt = $this->actingAs($superAdmin)->post('/notes/reopen', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'Devoir',
        'periode' => 'trimestre_1',
    ]);
    $superAdminAttempt->assertRedirect();
    expect($note->refresh()->isValidated())->toBeFalse();
});
