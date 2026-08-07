<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;

function createMatriculeGradeFixture(string $cycle = 'college'): array
{
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'cycle' => $cycle]);
    $matiere = Matiere::factory()->create(['coefficient' => 3]);

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

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

    return [$teacherUser, $classroom, $matiere, $student];
}

test('teacher can search a student by matricule and see their subjects with coefficients', function () {
    [$teacherUser, $classroom, $matiere, $student] = createMatriculeGradeFixture();

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.eleve', ['matricule' => $student->matricule]));

    $response->assertOk()
        ->assertSee($student->name)
        ->assertSee($classroom->name)
        ->assertSee($matiere->nom)
        ->assertSee((string) $matiere->coefficient);
});

test('searching an unknown matricule shows an error', function () {
    [$teacherUser] = createMatriculeGradeFixture();

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.eleve', ['matricule' => 'INEXISTANT']));

    $response->assertSessionHas('error');
});

test('a student outside the teacher assigned classes cannot be searched', function () {
    [$teacherUser] = createMatriculeGradeFixture();

    $otherSchoolYear = SchoolYear::factory()->create(['is_active' => false]);
    $otherClassroom = Classroom::factory()->create(['school_year_id' => $otherSchoolYear->id]);
    $outsideStudent = User::factory()->create(['role' => 'eleve']);
    Registration::factory()->create([
        'user_id' => $outsideStudent->id,
        'classroom_id' => $otherClassroom->id,
        'school_year_id' => $otherSchoolYear->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.eleve', ['matricule' => $outsideStudent->matricule]));

    $response->assertSessionHas('error');
});

test('teacher can save grades for a single student across all their subjects', function () {
    [$teacherUser, $classroom, $matiere, $student] = createMatriculeGradeFixture();

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.eleve.store'), [
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'periode' => 'trimestre_1',
        'grades' => [
            ['matiere_id' => $matiere->id, 'type_evaluation' => 'composition', 'valeur' => 15, 'appreciation' => 'Bien'],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('notes', [
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'valeur' => 15,
    ]);
});

test('teacher cannot save a grade for a subject not assigned to them in that class', function () {
    [$teacherUser, $classroom, , $student] = createMatriculeGradeFixture();
    $foreignMatiere = Matiere::factory()->create();

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.eleve.store'), [
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'periode' => 'trimestre_1',
        'grades' => [
            ['matiere_id' => $foreignMatiere->id, 'type_evaluation' => 'composition', 'valeur' => 15],
        ],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('notes', ['user_id' => $student->id, 'matiere_id' => $foreignMatiere->id]);
});

test('a primaire classroom only offers the composition evaluation type', function () {
    [$teacherUser, $classroom, $matiere, $student] = createMatriculeGradeFixture('primaire');

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.eleve', ['matricule' => $student->matricule]));

    $response->assertOk()
        ->assertSee('Composition')
        ->assertDontSee('Devoir');
});
