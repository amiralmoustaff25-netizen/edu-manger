<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

/**
 * Décision produit (2026-08-29, tranchée avec l'utilisateur) : un bulletin n'est visible
 * côté élève/parent qu'une fois les notes de la période validées par la direction (admin),
 * pas avant — jusqu'ici BulletinController::show()/generatePdf() l'exposaient sans aucune
 * condition dès qu'une note existait, même en pleine saisie par le professeur.
 */
function createBulletinFixture(): array
{
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    // Cycle fixé à 'primaire' (donc périodes en "trimestre_x") : le factory tire un cycle
    // au hasard sinon, ce qui ferait dépendre les assertions sur "trimestre_1" du hasard.
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve', 'is_active' => true])->assignRole('eleve');
    Registration::factory()->create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'active',
    ]);
    $matiere = Matiere::factory()->create();

    return [$student, $classroom, $matiere];
}

test('student cannot view a bulletin whose notes are not yet validated', function () {
    [$student, $classroom, $matiere] = createBulletinFixture();
    Note::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'valeur' => 15,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
    ]);

    $response = $this->actingAs($student)->get(route('bulletins.show', [$student, 'trimestre_1']));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('student can view a bulletin once all its notes are validated', function () {
    [$student, $classroom, $matiere] = createBulletinFixture();
    $admin = User::factory()->create(['role' => 'admin']);
    Note::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'valeur' => 15,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'validated_at' => now(),
        'validated_by' => $admin->id,
    ]);

    $this->actingAs($student)
        ->get(route('bulletins.show', [$student, 'trimestre_1']))
        ->assertOk();
});

test('a bulletin is not published while at least one of its notes is still unvalidated', function () {
    [$student, $classroom] = createBulletinFixture();
    $admin = User::factory()->create(['role' => 'admin']);
    $matiereA = Matiere::factory()->create();
    $matiereB = Matiere::factory()->create();
    Note::create([
        'user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiereA->id,
        'valeur' => 15, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1',
        'validated_at' => now(), 'validated_by' => $admin->id,
    ]);
    Note::create([
        'user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiereB->id,
        'valeur' => 12, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1',
    ]);

    $this->actingAs($student)
        ->get(route('bulletins.show', [$student, 'trimestre_1']))
        ->assertRedirect(route('dashboard'));
});

test('the "mes bulletins" page only offers voir/pdf links for published periods', function () {
    [$student, $classroom, $matiere] = createBulletinFixture();
    $admin = User::factory()->create(['role' => 'admin']);
    Note::create([
        'user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id,
        'valeur' => 15, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1',
        'validated_at' => now(), 'validated_by' => $admin->id,
    ]);

    $response = $this->actingAs($student)->get(route('student.bulletins'));

    $response->assertOk()
        ->assertSee(route('bulletins.show', [$student, 'trimestre_1']), false)
        ->assertSee('Pas encore disponible');
});

test('a parent cannot view their child bulletin before it is validated', function () {
    [$student] = createBulletinFixture();
    $parentUser = User::factory()->create(['role' => 'parent'])->assignRole('parent');
    $parentProfile = ParentModel::factory()->create(['user_id' => $parentUser->id, 'statut' => 'actif']);
    $parentProfile->students()->attach($student->id, ['lien_parente' => 'Pere']);

    $response = $this->actingAs($parentUser)->get(route('bulletins.show', [$student, 'trimestre_1']));

    $response->assertRedirect(route('dashboard'));
});

test('a teacher with generer-bulletins is not affected by the student publication gate', function () {
    [$student, $classroom, $matiere] = createBulletinFixture();
    $teacher = User::factory()->create(['role' => 'professeur'])->assignRole('professeur');
    Note::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'valeur' => 15,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
    ]);

    $this->actingAs($teacher)
        ->get(route('bulletins.show', [$student, 'trimestre_1']))
        ->assertOk();
});
