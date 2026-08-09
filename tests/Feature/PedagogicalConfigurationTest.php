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
