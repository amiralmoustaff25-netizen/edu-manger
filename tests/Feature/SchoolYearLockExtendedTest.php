<?php

use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use App\Support\SchoolYearStatus;
use Database\Seeders\RoleAndPermissionSeeder;

// Régression sous-étape C : le verrou d'année clôturée (SchoolYearGuardService), jusqu'ici
// limité aux paiements/frais/remises, est désormais aussi appliqué aux notes, aux classes
// (édition/suppression/affectation d'enseignants) et à la configuration pédagogique.

function createClosedYear(): SchoolYear
{
    return SchoolYear::create([
        'year_string' => '2023-2024',
        'is_active' => false,
        'status' => SchoolYearStatus::CLOSED,
    ]);
}

beforeEach(function () {
    test()->seed(RoleAndPermissionSeeder::class);
});

test('a teacher cannot record a grade in a locked school year', function () {
    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $matiere = Matiere::factory()->create();

    $teacherUser = User::factory()->create(['role' => 'professeur']);
    $teacherUser->assignRole('professeur');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
    $classroom->teachers()->attach($teacher->id, ['annee_scolaire' => $year->year_string, 'matiere_id' => $matiere->id, 'volume_horaire_hebdo' => 4]);

    $student = User::factory()->create(['role' => 'eleve']);
    Registration::create([
        'user_id' => $student->id, 'classroom_id' => $classroom->id, 'school_year_id' => $year->id,
        'registration_fee_paid' => 0, 'monthly_fee' => 0, 'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string, 'matricule' => 'ELE-TEST-0001', 'status' => 'active',
    ]);

    $response = test()->actingAs($teacherUser)->post('/professeur/notes', [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
        'grades' => [['user_id' => $student->id, 'valeur' => 15]],
    ]);

    $response->assertSessionHasErrors('school_year');
    expect(Note::count())->toBe(0);
});

test('validating a batch of grades is blocked on a locked school year', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $matiere = Matiere::factory()->create();

    $response = test()->actingAs($admin)->post(route('notes.validate'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
    ]);

    $response->assertSessionHasErrors('school_year');
});

test('super admin can still validate grades on a locked school year', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $matiere = Matiere::factory()->create();
    Note::factory()->create(['classroom_id' => $classroom->id, 'matiere_id' => $matiere->id, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1', 'validated_at' => null]);

    $response = test()->actingAs($superAdmin)->post(route('notes.validate'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
    ]);

    $response->assertSessionDoesntHaveErrors('school_year');
});

test('super admin can still reopen grades on a locked school year', function () {
    // Seul un super-admin a la permission 'rouvrir-notes-validees' (NotePolicy::reopen()) —
    // aucun autre rôle ne peut donc jamais être bloqué par le verrou d'année sur cette action
    // précise, puisque le super-admin le contourne déjà systématiquement. Le verrou reste
    // posé ici par cohérence/défense en profondeur si cette permission venait à s'élargir.
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $matiere = Matiere::factory()->create();
    Note::factory()->create(['classroom_id' => $classroom->id, 'matiere_id' => $matiere->id, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1', 'validated_at' => now(), 'validated_by' => $superAdmin->id]);

    $response = test()->actingAs($superAdmin)->post(route('notes.reopen'), [
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'type_evaluation' => 'composition',
        'periode' => 'trimestre_1',
    ]);

    $response->assertSessionDoesntHaveErrors('school_year');
});

test('a classroom in a locked school year cannot be edited', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);

    $response = test()->actingAs($admin)->put(route('classrooms.update', $classroom), [
        'level' => 'CM2', 'section' => 'B', 'max_students' => 30,
    ]);

    $response->assertSessionHasErrors('school_year');
    expect($classroom->fresh()->name)->toBe('CM2');
});

test('a classroom without registrations in a locked school year cannot be deleted', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);

    $response = test()->actingAs($admin)->delete(route('classrooms.destroy', $classroom));

    $response->assertSessionHasErrors('school_year');
    expect(Classroom::find($classroom->id))->not->toBeNull();
});

test('attaching a teacher to a classroom in a locked school year is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $teacher = Teacher::factory()->create();

    $response = test()->actingAs($admin)->post(route('classrooms.attach-teacher', $classroom), [
        'teacher_id' => $teacher->id, 'volume_horaire_hebdo' => 4,
    ]);

    $response->assertSessionHasErrors('school_year');
});

test('storing pedagogical assignments for a locked school year is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $teacher = Teacher::factory()->create();
    $matiere = Matiere::factory()->create();

    $response = test()->actingAs($admin)->post(route('pedagogical-configuration.assignments.store'), [
        'teacher_matricule' => $teacher->matricule,
        'classroom_ids' => [$classroom->id],
        'classroom_volumes' => [$classroom->id => 4],
        'matiere_ids' => [$matiere->id],
        'school_year_id' => $year->id,
    ]);

    $response->assertSessionHasErrors('school_year');
    expect(PedagogicalAssignment::count())->toBe(0);
});

test('toggling a pedagogical assignment in a locked school year is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $classroom = Classroom::create(['name' => 'CM2', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $teacher = Teacher::factory()->create();
    $matiere = Matiere::factory()->create();
    $assignment = PedagogicalAssignment::create([
        'teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id,
        'school_year_id' => $year->id, 'is_active' => true,
    ]);

    $response = test()->actingAs($admin)->patch(route('pedagogical-configuration.assignments.toggle', $assignment));

    $response->assertSessionHasErrors('school_year');
    expect($assignment->fresh()->is_active)->toBeTrue();
});

test('storing an academic period for a locked school year is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();

    $response = test()->actingAs($admin)->post(route('pedagogical-configuration.periods.store'), [
        'school_year_id' => $year->id, 'name' => 'Trimestre 1',
        'starts_at' => '2023-09-01', 'ends_at' => '2023-12-20',
    ]);

    $response->assertSessionHasErrors('school_year');
    expect(AcademicPeriod::count())->toBe(0);
});

test('toggling an academic period in a locked school year is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $period = AcademicPeriod::create([
        'school_year_id' => $year->id, 'name' => 'Trimestre 1', 'code' => 'trimestre_1',
        'position' => 1, 'starts_at' => '2023-09-01', 'ends_at' => '2023-12-20',
    ]);

    $response = test()->actingAs($admin)->patch(route('pedagogical-configuration.periods.toggle', $period));

    $response->assertSessionHasErrors('school_year');
});

test('updating grade settings for a locked school year is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();

    $response = test()->actingAs($admin)->put(route('pedagogical-configuration.settings.update', $year), [
        'organization_mode' => 'trimesters', 'default_scale' => 20, 'minimum_grade' => 0, 'decimal_places' => 1,
    ]);

    $response->assertSessionHasErrors('school_year');
});

test('storing a subject configuration for a locked school year is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $year = createClosedYear();
    $matiere = Matiere::factory()->create();

    $response = test()->actingAs($admin)->post(route('pedagogical-configuration.subjects.store'), [
        'school_year_id' => $year->id, 'matiere_id' => $matiere->id, 'coefficient' => 2,
    ]);

    $response->assertSessionHasErrors('school_year');
});
