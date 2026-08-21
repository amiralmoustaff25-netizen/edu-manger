<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\SubjectConfiguration;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GradeCalculationService;

function createBaremeGradeFixture(): array
{
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $matiere = Matiere::factory()->create(['nom' => 'Mathématiques']);

    SubjectConfiguration::create([
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'cycle' => 'primaire',
        'bareme' => 80,
        'is_active' => true,
    ]);

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'volume_horaire_hebdo' => 0,
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

test('a teacher assigned only via Affectations pédagogiques (PedagogicalAssignment) sees their classroom and subject on the by-class grade entry page', function () {
    // Régression : index()/store() vérifiaient l'ancien pivot teacher_classroom
    // (Teacher::classrooms(), écran "Gestion des enseignants" retiré de la navigation),
    // jamais alimenté par le seul écran d'affectation actuellement accessible
    // (PedagogicalConfigurationController::storeAssignments, PedagogicalAssignment) — un
    // professeur affecté uniquement via cet écran ne voyait donc jamais sa classe ici.
    [$teacherUser, $classroom, $matiere] = createBaremeGradeFixture();

    $response = $this->actingAs($teacherUser)->get(route('professeur.notes.index', ['classroom_id' => $classroom->id]));

    $response->assertOk()
        ->assertSee($classroom->name)
        ->assertSee($matiere->nom);
});

test('the by-class grade entry accepts a note above 20 when the subject barème allows it', function () {
    [$teacherUser, $classroom, $matiere, $student] = createBaremeGradeFixture();

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.store'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 65, 'appreciation' => 'Bien'],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('notes', ['user_id' => $student->id, 'matiere_id' => $matiere->id, 'valeur' => 65]);
});

test('the by-class grade entry rejects a note above the configured barème', function () {
    [$teacherUser, $classroom, $matiere, $student] = createBaremeGradeFixture();

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.store'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 95, 'appreciation' => 'Trop'],
        ],
    ]);

    $response->assertSessionHasErrors('grades.0.valeur');
    $this->assertDatabaseMissing('notes', ['user_id' => $student->id, 'matiere_id' => $matiere->id]);
});

test('the by-class grade entry still caps at 20 for a subject without a configured barème', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'cycle' => 'college']);
    $matiere = Matiere::factory()->create();

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id, 'volume_horaire_hebdo' => 2, 'is_active' => true,
    ]);

    $student = User::factory()->create(['role' => 'eleve']);
    Registration::factory()->create(['user_id' => $student->id, 'classroom_id' => $classroom->id, 'school_year_id' => $schoolYear->id, 'status' => 'active']);

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.store'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'grades' => [
            ['user_id' => $student->id, 'valeur' => 25],
        ],
    ]);

    $response->assertSessionHasErrors('grades.0.valeur');
});

test('the by-matricule grade entry accepts a note above 20 for a subject with a configured barème', function () {
    [$teacherUser, $classroom, $matiere, $student] = createBaremeGradeFixture();

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.eleve.store'), [
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'periode' => 'trimestre_1',
        'grades' => [
            ['matiere_id' => $matiere->id, 'type_evaluation' => 'composition', 'valeur' => 72],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('notes', ['user_id' => $student->id, 'matiere_id' => $matiere->id, 'valeur' => 72]);
});

test('the by-matricule grade entry rejects a note above the configured barème', function () {
    [$teacherUser, $classroom, $matiere, $student] = createBaremeGradeFixture();

    $response = $this->actingAs($teacherUser)->post(route('professeur.notes.eleve.store'), [
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'periode' => 'trimestre_1',
        'grades' => [
            ['matiere_id' => $matiere->id, 'type_evaluation' => 'composition', 'valeur' => 81],
        ],
    ]);

    $response->assertSessionHasErrors('grades');
    $this->assertDatabaseMissing('notes', ['user_id' => $student->id, 'matiere_id' => $matiere->id]);
});

test('an admin can set a barème for a primaire subject via the pedagogical configuration screen, and it is then used for grade validation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $matiere = Matiere::factory()->create(['nom' => 'Découverte du monde']);

    $response = $this->actingAs($admin)->post(route('pedagogical-configuration.subjects.store'), [
        'school_year_id' => $schoolYear->id,
        'matiere_id' => $matiere->id,
        'cycle' => 'primaire',
        'coefficient' => 1,
        'bareme' => 40,
    ]);

    $response->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('subject_configurations', [
        'school_year_id' => $schoolYear->id,
        'matiere_id' => $matiere->id,
        'cycle' => 'primaire',
        'bareme' => 40,
    ]);

    expect(app(GradeCalculationService::class)->resolveBareme($matiere, $classroom, $schoolYear->id))->toBe(40.0);
});

test('the bulletin page shows the barème column and points obtained for a configured primaire subject', function () {
    [$teacherUser, $classroom, $matiere, $student] = createBaremeGradeFixture();

    Note::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'valeur' => 65,
    ]);

    $response = $this->actingAs($teacherUser)->get(route('bulletins.show', [$student, 'trimestre_1']));

    $response->assertOk()
        ->assertSee('Barème')
        ->assertSee('Points obtenus')
        ->assertDontSee('Moy. × Coef');
});
