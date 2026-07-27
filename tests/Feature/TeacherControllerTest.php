<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\TeachingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

test('admin can view teachers index', function () {
    $response = $this->get(route('teachers.index'));

    $response->assertOk()
        ->assertSee('Gestion des professeurs');
});

test('admin can create a new teacher', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);

    $response = $this->post(route('teachers.store'), [
        'nom' => 'Jean',
        'prenom' => 'Dupont',
        'email' => 'jean.dupont@ecole.sn',
        'date_naissance' => '1985-05-15',
        'lieu_naissance' => 'Dakar',
        'sexe' => 'masculin',
        'nationalite' => 'Sénégalaise',
        'diplomes' => 'Licence en mathématiques',
        'etablissements_formation' => 'UCAD',
        'statut' => 'fonctionnaire',
        'date_recrutement' => '2017-09-01',
        'specialites' => 'Mathématiques, Physique',
        'filiation' => 'Fils de M. Dupont',
        'contact_urgence_nom' => 'M. Dupont',
        'contact_urgence_tel' => '770000001',
        'rib' => 'FR761234598765',
        'nombre_heures_semaine' => 18,
        'telephone' => '770000001',
        'classrooms' => [
            ['classroom_id' => $classroom->id, 'matiere_id' => null, 'volume_horaire_hebdo' => 12],
        ],
    ]);

    $response->assertRedirect(route('teachers.index'));

    $this->assertDatabaseHas('users', ['email' => 'jean.dupont@ecole.sn']);
    $this->assertDatabaseHas('teachers', ['statut' => 'fonctionnaire']);
});

test('teacher index displays hours from active pedagogical assignments', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $matiere = Matiere::factory()->create();

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'volume_horaire_hebdo' => 7,
        'is_active' => true,
    ]);

    $this->get(route('teachers.index'))
        ->assertOk()
        ->assertSee('7h');
});

test('teacher detail displays assignment course progress', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $teacher = Teacher::factory()->create();
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $matiere = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $assignment = PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);
    TeachingSession::create([
        'pedagogical_assignment_id' => $assignment->id,
        'taught_on' => now()->startOfWeek()->addDay(),
        'duration_hours' => 2,
        'recorded_by' => $teacher->user_id,
    ]);

    $this->get(route('teachers.show', $teacher))
        ->assertOk()
        ->assertSee('Classes et évolution des cours')
        ->assertSee($classroom->name)
        ->assertSee('Mathématiques')
        ->assertSee('2 h réalisées cette semaine')
        ->assertSee('2 h restantes cette semaine');
});

test('admin can view a teacher detail page', function () {
    $teacher = Teacher::factory()->create();

    $response = $this->get(route('teachers.show', $teacher));

    $response->assertOk()
        ->assertSee($teacher->matricule);
});

test('admin can update a teacher', function () {
    $teacher = Teacher::factory()->create();

    $response = $this->put(route('teachers.update', $teacher), [
        'nom' => 'Pierre',
        'prenom' => 'Martin',
        'email' => 'pierre.martin@ecole.sn',
        'date_naissance' => $teacher->date_naissance->format('Y-m-d'),
        'lieu_naissance' => $teacher->lieu_naissance,
        'sexe' => $teacher->sexe,
        'nationalite' => $teacher->nationalite,
        'diplomes' => $teacher->diplomes,
        'etablissements_formation' => $teacher->etablissements_formation,
        'statut' => $teacher->statut,
        'date_recrutement' => $teacher->date_recrutement->format('Y-m-d'),
        'specialites' => 'Physique, Chimie',
        'filiation' => $teacher->filiation,
        'contact_urgence_nom' => $teacher->contact_urgence_nom,
        'contact_urgence_tel' => $teacher->contact_urgence_tel,
        'rib' => '',
        'nombre_heures_semaine' => $teacher->nombre_heures_semaine,
        'telephone' => $teacher->user->telephone,
    ]);

    $response->assertRedirect(route('teachers.show', $teacher));
    $this->assertDatabaseHas('users', ['email' => 'pierre.martin@ecole.sn']);
});

test('admin can delete a teacher', function () {
    $teacher = Teacher::factory()->create();

    $response = $this->delete(route('teachers.destroy', $teacher));

    $response->assertRedirect(route('teachers.index'));
    $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
});
