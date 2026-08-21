<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
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

test('the search-by-matricule page links to a live bulletin preview for the student found', function () {
    [$teacherUser, $classroom, $matiere, $student] = createMatriculeGradeFixture();

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.eleve', ['matricule' => $student->matricule, 'periode' => 'trimestre_2']));

    $response->assertOk()->assertSee(route('bulletins.show', [$student, 'trimestre_2']), false);
});

test('a primaire classroom only offers the composition evaluation type', function () {
    [$teacherUser, $classroom, $matiere, $student] = createMatriculeGradeFixture('primaire');

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.eleve', ['matricule' => $student->matricule]));

    $response->assertOk()
        ->assertSee('Composition')
        ->assertDontSee('Devoir');
});

test('a previously saved composition grade pre-fills on the search-by-matricule page for a primaire classroom', function () {
    // Régression : le préremplissage cherchait toujours la note existante sous le type
    // 'devoir' en dur, qui n'existe même pas en primaire (seul 'composition' y est
    // autorisé) — une note déjà saisie n'apparaissait donc jamais en revenant sur cette
    // page, malgré la validation ci-dessus qui la refuserait de toute façon si resaisie
    // sous 'devoir'.
    [$teacherUser, $classroom, $matiere, $student] = createMatriculeGradeFixture('primaire');

    Note::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'valeur' => 14.5,
        'appreciation' => 'Bon travail',
    ]);

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.eleve', ['matricule' => $student->matricule, 'periode' => 'trimestre_1']));

    $response->assertOk()->assertSee('Bon travail');
});

test('a primaire classroom rejects the devoir evaluation type even via a direct request, not just the dropdown', function () {
    // Le formulaire ne propose que "composition" pour le primaire (voir le test
    // précédent), mais rien n'empêchait un envoi direct du formulaire avec "devoir"
    // avant que cette règle métier soit aussi imposée côté serveur.
    [$teacherUser, $classroom, $matiere, $student] = createMatriculeGradeFixture('primaire');

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.eleve.store'), [
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'periode' => 'trimestre_1',
        'grades' => [
            ['matiere_id' => $matiere->id, 'type_evaluation' => 'devoir', 'valeur' => 15],
        ],
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseMissing('notes', ['user_id' => $student->id, 'matiere_id' => $matiere->id]);
});
