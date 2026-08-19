<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['role' => 'super-admin']);
    $this->admin->assignRole('super-admin');
    $this->actingAs($this->admin);
    $this->schoolYear = SchoolYear::factory()->create(['is_active' => true, 'year_string' => '2026-2027']);
});

test('super admin can access the pedagogical configuration center', function () {
    $this->get(route('pedagogical-configuration.index'))
        ->assertOk()
        ->assertSee('Configuration pédagogique')
        ->assertSee('2026-2027');
});

test('the grades tab now links the note lock/reopen workflow to the UI', function () {
    // Régression Phase 3 (finding H8) : notes.validate/notes.reopen existaient côté serveur
    // (testés dans GradeValidationWorkflowTest) mais aucune vue n'y menait — même pas une
    // page orpheline. L'onglet "Notes & verrouillage" annonçait déjà cette fonctionnalité
    // dans son libellé, sans jamais la construire.
    $this->get(route('pedagogical-configuration.index'))
        ->assertOk()
        ->assertSee(route('notes.validate'), false)
        ->assertSee(route('notes.reopen'), false);
});

test('super admin can create multi-subject pedagogical assignments', function () {
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
    $math = Matiere::factory()->create();

    $this->post(route('pedagogical-configuration.assignments.store'), [
        'teacher_matricule' => $teacher->matricule,
        'classroom_ids' => [$classroom->id],
        'classroom_volumes' => [$classroom->id => 4],
        'matiere_ids' => [$math->id],
        'new_subject_names' => 'Arabe',
        'school_year_id' => $this->schoolYear->id,
        'volume_horaire_hebdo' => 4,
    ])->assertRedirect(route('pedagogical-configuration.assignments', ['school_year_id' => $this->schoolYear->id]));

    $this->assertDatabaseCount('pedagogical_assignments', 2);
    $this->assertDatabaseHas('matieres', ['nom' => 'Arabe']);
    $this->get(route('pedagogical-configuration.assignments', ['school_year_id' => $this->schoolYear->id]))
        ->assertOk()
        ->assertSee($teacher->user->name)
        ->assertSee($classroom->name)
        ->assertSee('Arabe');
});

test('a primaire classroom assignment does not require a weekly hour volume', function () {
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);

    // Aucune classroom_volumes envoyée du tout : ne doit pas être rejeté pour le primaire,
    // contrairement au secondaire (voir test générique ci-dessus qui l'exige).
    $this->post(route('pedagogical-configuration.assignments.store'), [
        'teacher_matricule' => $teacher->matricule,
        'classroom_ids' => [$classroom->id],
        'matiere_ids' => [$math->id],
        'school_year_id' => $this->schoolYear->id,
    ])->assertRedirect(route('pedagogical-configuration.assignments', ['school_year_id' => $this->schoolYear->id]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('pedagogical_assignments', [
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $math->id,
        'volume_horaire_hebdo' => 0,
    ]);
});

test('a primaire classroom cannot have two different main teachers for general subjects', function () {
    $firstTeacher = Teacher::factory()->create();
    $secondTeacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $french = Matiere::factory()->create(['nom' => 'Français']);

    PedagogicalAssignment::create([
        'teacher_id' => $firstTeacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $math->id,
        'school_year_id' => $this->schoolYear->id,
        'volume_horaire_hebdo' => 0,
        'is_active' => true,
    ]);

    $response = $this->post(route('pedagogical-configuration.assignments.store'), [
        'teacher_matricule' => $secondTeacher->matricule,
        'classroom_ids' => [$classroom->id],
        'matiere_ids' => [$french->id],
        'school_year_id' => $this->schoolYear->id,
    ]);

    $response->assertSessionHasErrors('classroom_ids');
    $this->assertDatabaseMissing('pedagogical_assignments', ['teacher_id' => $secondTeacher->id]);
});

test('a primaire main teacher cannot be assigned as main teacher of a second classroom', function () {
    $teacher = Teacher::factory()->create();
    $firstClassroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
    $secondClassroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $firstClassroom->id,
        'matiere_id' => $math->id,
        'school_year_id' => $this->schoolYear->id,
        'volume_horaire_hebdo' => 0,
        'is_active' => true,
    ]);

    $response = $this->post(route('pedagogical-configuration.assignments.store'), [
        'teacher_matricule' => $teacher->matricule,
        'classroom_ids' => [$secondClassroom->id],
        'matiere_ids' => [$math->id],
        'school_year_id' => $this->schoolYear->id,
    ]);

    $response->assertSessionHasErrors('teacher_matricule');
    $this->assertDatabaseMissing('pedagogical_assignments', ['classroom_id' => $secondClassroom->id]);
});

test('the assignments screen exposes which teacher is already principal of which primaire classroom, for the form to grey out invalid choices', function () {
    // Le blocage serveur existait déjà (tests ci-dessus) mais l'admin ne le découvrait
    // qu'après avoir rempli tout le formulaire et essuyé une erreur. Ces données
    // permettent au formulaire (Alpine.js) de griser directement les classes primaire
    // qu'un professeur ne peut pas se voir attribuer.
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $math->id,
        'school_year_id' => $this->schoolYear->id,
        'volume_horaire_hebdo' => 0,
        'is_active' => true,
    ]);

    $response = $this->get(route('pedagogical-configuration.assignments', ['school_year_id' => $this->schoolYear->id]));

    $response->assertOk();
    expect($response->viewData('primaireClassroomPrincipals'))->toHaveKey($classroom->id);
    expect($response->viewData('primaireClassroomPrincipals')[$classroom->id]['teacher_matricule'])->toBe($teacher->matricule);
    expect($response->viewData('teacherPrimairePrincipalOf'))->toHaveKey($teacher->matricule);
    expect($response->viewData('teacherPrimairePrincipalOf')[$teacher->matricule]['classroom_id'])->toBe($classroom->id);
});

test('anglais and musique are exempt from the primaire main-teacher exclusivity rules', function () {
    $mainTeacher = Teacher::factory()->create();
    $englishTeacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $english = Matiere::factory()->create(['nom' => 'Anglais']);

    PedagogicalAssignment::create([
        'teacher_id' => $mainTeacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $math->id,
        'school_year_id' => $this->schoolYear->id,
        'volume_horaire_hebdo' => 0,
        'is_active' => true,
    ]);

    // Une classe déjà pourvue d'un professeur principal peut quand même recevoir un
    // professeur d'anglais dédié, différent du principal.
    $this->post(route('pedagogical-configuration.assignments.store'), [
        'teacher_matricule' => $englishTeacher->matricule,
        'classroom_ids' => [$classroom->id],
        'matiere_ids' => [$english->id],
        'school_year_id' => $this->schoolYear->id,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('pedagogical_assignments', [
        'teacher_id' => $englishTeacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $english->id,
    ]);
});

test('super admin can edit and delete an existing pedagogical assignment', function () {
    $teacher = Teacher::factory()->create();
    $classroomA = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'college']);
    $classroomB = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'college']);
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $french = Matiere::factory()->create(['nom' => 'Français']);

    $assignment = PedagogicalAssignment::create([
        'teacher_id' => $teacher->id, 'classroom_id' => $classroomA->id, 'matiere_id' => $math->id,
        'school_year_id' => $this->schoolYear->id, 'volume_horaire_hebdo' => 4, 'is_active' => true,
    ]);

    $this->patch(route('pedagogical-configuration.assignments.update', $assignment), [
        'classroom_id' => $classroomB->id, 'matiere_id' => $french->id, 'volume_horaire_hebdo' => 6,
    ])->assertSessionDoesntHaveErrors();

    $assignment->refresh();
    expect($assignment->classroom_id)->toBe($classroomB->id);
    expect($assignment->matiere_id)->toBe($french->id);
    expect((float) $assignment->volume_horaire_hebdo)->toBe(6.0);

    $this->delete(route('pedagogical-configuration.assignments.destroy', $assignment))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('pedagogical_assignments', ['id' => $assignment->id]);
});

test('editing an assignment into a primaire classroom respects the main-teacher exclusivity rules', function () {
    $existingPrincipal = Teacher::factory()->create();
    $otherTeacher = Teacher::factory()->create();
    $primaireClassroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'primaire']);
    $collegeClassroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'college']);
    $math = Matiere::factory()->create(['nom' => 'Mathématiques']);

    PedagogicalAssignment::create([
        'teacher_id' => $existingPrincipal->id, 'classroom_id' => $primaireClassroom->id, 'matiere_id' => $math->id,
        'school_year_id' => $this->schoolYear->id, 'volume_horaire_hebdo' => 0, 'is_active' => true,
    ]);
    $assignment = PedagogicalAssignment::create([
        'teacher_id' => $otherTeacher->id, 'classroom_id' => $collegeClassroom->id, 'matiere_id' => $math->id,
        'school_year_id' => $this->schoolYear->id, 'volume_horaire_hebdo' => 3, 'is_active' => true,
    ]);

    $this->patch(route('pedagogical-configuration.assignments.update', $assignment), [
        'classroom_id' => $primaireClassroom->id, 'matiere_id' => $math->id,
    ])->assertSessionHasErrors('classroom_id');

    expect($assignment->refresh()->classroom_id)->toBe($collegeClassroom->id);
});

test('the assignments table paginates and lists every pedagogical assignment', function () {
    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
    $matiere = Matiere::factory()->create();
    for ($i = 0; $i < 7; $i++) {
        PedagogicalAssignment::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'school_year_id' => $this->schoolYear->id,
            'volume_horaire_hebdo' => 2,
            'is_active' => true,
        ]);
    }

    $response = $this->get(route('pedagogical-configuration.assignments', ['school_year_id' => $this->schoolYear->id]));

    $response->assertOk();
    // Toutes les affectations doivent être accessibles (paginées, pas tronquées
    // silencieusement à 5 comme avant correction du bug de régression M5).
    expect($response->viewData('assignments'))->toHaveCount(7);
    expect($response->viewData('assignments')->total())->toBe(7);
});

test('super admin can create a matière and its cycle coefficient in a single submission, including a lycée série', function () {
    // Le formulaire "Matière & coefficient" fusionne désormais création de la matière et
    // configuration du coefficient par cycle : une nouvelle matière peut être saisie
    // directement ici (subject_name), sans passer par un autre formulaire au préalable.
    $this->post(route('pedagogical-configuration.subjects.store'), [
        'school_year_id' => $this->schoolYear->id,
        'subject_name' => 'Philosophie',
        'cycle' => 'lycee',
        'serie' => 'L',
        'coefficient' => 4,
    ])->assertSessionDoesntHaveErrors();

    $matiere = Matiere::where('nom', 'Philosophie')->firstOrFail();
    $this->assertDatabaseHas('subject_configurations', [
        'matiere_id' => $matiere->id, 'school_year_id' => $this->schoolYear->id,
        'cycle' => 'lycee', 'serie' => 'L', 'coefficient' => 4,
    ]);
});

test('the série is ignored when the cycle is not lycée', function () {
    $matiere = Matiere::factory()->create();

    $this->post(route('pedagogical-configuration.subjects.store'), [
        'school_year_id' => $this->schoolYear->id,
        'matiere_id' => $matiere->id,
        'cycle' => 'college',
        'serie' => 'S',
        'coefficient' => 3,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('subject_configurations', [
        'matiere_id' => $matiere->id, 'school_year_id' => $this->schoolYear->id,
        'cycle' => 'college', 'serie' => null, 'coefficient' => 3,
    ]);
});

test('super admin can create, edit and delete an unused matière', function () {
    $this->post(route('pedagogical-configuration.matieres.store'), [
        'nom' => 'Philosophie', 'coefficient' => 2,
    ])->assertSessionDoesntHaveErrors();

    $matiere = Matiere::where('nom', 'Philosophie')->firstOrFail();

    $this->patch(route('pedagogical-configuration.matieres.update', $matiere), [
        'nom' => 'Philosophie et citoyenneté', 'coefficient' => 3,
    ])->assertSessionDoesntHaveErrors();

    expect($matiere->refresh()->nom)->toBe('Philosophie et citoyenneté');
    expect((float) $matiere->coefficient)->toBe(3.0);

    $this->delete(route('pedagogical-configuration.matieres.destroy', $matiere))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('matieres', ['id' => $matiere->id]);
});

test('a newly created matière defaults to a base barème of 20, editable and used as the resolveBareme fallback', function () {
    $this->post(route('pedagogical-configuration.matieres.store'), [
        'nom' => 'Philosophie', 'coefficient' => 2,
    ])->assertSessionDoesntHaveErrors();

    $matiere = Matiere::where('nom', 'Philosophie')->firstOrFail();
    expect((float) $matiere->bareme)->toBe(20.0);

    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id, 'cycle' => 'college']);
    expect(app(\App\Services\GradeCalculationService::class)->resolveBareme($matiere, $classroom, $this->schoolYear->id))->toBe(20.0);

    $this->patch(route('pedagogical-configuration.matieres.update', $matiere), [
        'nom' => 'Philosophie', 'coefficient' => 2, 'bareme' => 100,
    ])->assertSessionDoesntHaveErrors();

    expect((float) $matiere->refresh()->bareme)->toBe(100.0);
    expect(app(\App\Services\GradeCalculationService::class)->resolveBareme($matiere, $classroom, $this->schoolYear->id))->toBe(100.0);
});

test('renaming a matière to a name already used by another matière is rejected', function () {
    Matiere::factory()->create(['nom' => 'Anglais']);
    $matiere = Matiere::factory()->create(['nom' => 'Espagnol']);

    $this->patch(route('pedagogical-configuration.matieres.update', $matiere), [
        'nom' => 'Anglais', 'coefficient' => 1,
    ])->assertSessionHasErrors('nom');

    expect($matiere->refresh()->nom)->toBe('Espagnol');
});

test('a matière with pedagogical assignments cannot be deleted', function () {
    $matiere = Matiere::factory()->create();
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id,
        'school_year_id' => $this->schoolYear->id, 'volume_horaire_hebdo' => 4, 'is_active' => true,
    ]);

    $this->delete(route('pedagogical-configuration.matieres.destroy', $matiere))
        ->assertSessionHasErrors('matiere');

    $this->assertDatabaseHas('matieres', ['id' => $matiere->id]);
});

test('a matière with a subject configuration (coefficient/barème) cannot be deleted', function () {
    $matiere = Matiere::factory()->create();

    $this->post(route('pedagogical-configuration.subjects.store'), [
        'school_year_id' => $this->schoolYear->id, 'matiere_id' => $matiere->id, 'coefficient' => 2,
    ])->assertSessionDoesntHaveErrors();

    $this->delete(route('pedagogical-configuration.matieres.destroy', $matiere))
        ->assertSessionHasErrors('matiere');

    $this->assertDatabaseHas('matieres', ['id' => $matiere->id]);
});

test('super admin can configure periods and grade rules', function () {
    $this->post(route('pedagogical-configuration.periods.store'), [
        'school_year_id' => $this->schoolYear->id,
        'name' => 'Trimestre 1',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-12-20',
        'grade_entry_starts_at' => '2026-10-01',
        'grade_entry_ends_at' => '2026-12-18',
    ])->assertSessionHasNoErrors();

    $this->put(route('pedagogical-configuration.settings.update', $this->schoolYear), [
        'organization_mode' => 'trimesters',
        'default_scale' => 20,
        'minimum_grade' => 0,
        'allow_decimals' => 1,
        'decimal_places' => 1,
        'allow_appreciations' => 1,
        'administrative_validation_required' => 1,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('academic_periods', ['school_year_id' => $this->schoolYear->id, 'code' => 'trimestre_1']);
    $this->assertDatabaseHas('grade_settings', ['school_year_id' => $this->schoolYear->id, 'default_scale' => 20]);
});
