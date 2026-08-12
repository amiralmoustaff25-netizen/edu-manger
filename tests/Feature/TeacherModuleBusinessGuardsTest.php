<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->admin->assignRole('admin');
});

function teacherUpdatePayload(Teacher $teacher, array $overrides = []): array
{
    return array_merge([
        'nom' => 'Diallo',
        'prenom' => 'Amadou',
        'email' => $teacher->user->email,
        'date_naissance' => $teacher->date_naissance->format('Y-m-d'),
        'lieu_naissance' => $teacher->lieu_naissance,
        'sexe' => $teacher->sexe,
        'nationalite' => $teacher->nationalite,
        'diplomes' => $teacher->diplomes,
        'etablissements_formation' => $teacher->etablissements_formation,
        'statut' => $teacher->statut,
        'date_recrutement' => $teacher->date_recrutement->format('Y-m-d'),
        'specialites' => 'Mathématiques',
        'filiation' => $teacher->filiation,
        'contact_urgence_nom' => $teacher->contact_urgence_nom,
        'contact_urgence_tel' => $teacher->contact_urgence_tel,
        'rib' => '',
        'nombre_heures_semaine' => $teacher->nombre_heures_semaine,
        'telephone' => $teacher->user->telephone,
    ], $overrides);
}

test('updating a teacher without retyping the RIB does not erase the existing one (C1)', function () {
    $teacher = Teacher::factory()->create(['rib' => 'SN08 SN01 0152 0000 0025 0017 5401']);

    $this->actingAs($this->admin)
        ->put(route('teachers.update', $teacher), teacherUpdatePayload($teacher))
        ->assertRedirect(route('teachers.show', $teacher));

    expect($teacher->fresh()->rib)->toBe('SN08 SN01 0152 0000 0025 0017 5401');
});

test('submitting a new RIB on update replaces the existing one (C1)', function () {
    $teacher = Teacher::factory()->create(['rib' => 'OLD-RIB-VALUE']);

    $this->actingAs($this->admin)
        ->put(route('teachers.update', $teacher), teacherUpdatePayload($teacher, ['rib' => 'NEW-RIB-VALUE']))
        ->assertRedirect(route('teachers.show', $teacher));

    expect($teacher->fresh()->rib)->toBe('NEW-RIB-VALUE');
});

test('updating a teacher does not wipe classroom assignments made from the dedicated screen (C2)', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $teacher = Teacher::factory()->create();

    $teacher->classrooms()->attach($classroom->id, [
        'annee_scolaire' => $schoolYear->year_string,
        'matiere_id' => null,
        'volume_horaire_hebdo' => 10,
    ]);

    $this->actingAs($this->admin)
        ->put(route('teachers.update', $teacher), teacherUpdatePayload($teacher))
        ->assertRedirect(route('teachers.show', $teacher));

    expect($teacher->classrooms()->count())->toBe(1);
});

test('archiving a teacher with active pedagogical assignments is blocked (C3)', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $matiere = Matiere::factory()->create();
    $teacher = Teacher::factory()->create();

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('teachers.destroy', $teacher))
        ->assertSessionHasErrors('teacher');

    expect($teacher->fresh()->trashed())->toBeFalse();
});

test('archiving a teacher without active assignments also deactivates the underlying user account (C3)', function () {
    $teacher = Teacher::factory()->create();
    $userId = $teacher->user_id;

    $this->actingAs($this->admin)
        ->delete(route('teachers.destroy', $teacher))
        ->assertRedirect(route('teachers.index'));

    expect($teacher->fresh()->trashed())->toBeTrue();
    $user = User::withTrashed()->find($userId);
    expect($user->trashed())->toBeTrue();
    expect($user->is_active)->toBeFalse();
});

test('an archived teacher cannot log in, closing the orphaned account gap (C3)', function () {
    $teacher = Teacher::factory()->create();
    $teacher->user->update(['password' => Hash::make('Password123!')]);
    $matricule = $teacher->user->matricule;

    $this->actingAs($this->admin)->delete(route('teachers.destroy', $teacher));
    auth()->logout();

    $this->post(route('login'), [
        'matricule' => $matricule,
        'password' => 'Password123!',
    ]);

    $this->assertGuest();
});

test('an archived teacher can be restored and regains access, along with the classroom pivot data (C4)', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id]);
    $teacher = Teacher::factory()->create();
    $teacher->classrooms()->attach($classroom->id, [
        'annee_scolaire' => $schoolYear->year_string,
        'matiere_id' => null,
        'volume_horaire_hebdo' => 8,
    ]);
    $userId = $teacher->user_id;

    $this->actingAs($this->admin)->delete(route('teachers.destroy', $teacher));

    $this->actingAs($this->admin)
        ->post(route('teachers.restore', $teacher->id))
        ->assertRedirect(route('teachers.index'));

    $restoredTeacher = Teacher::find($teacher->id);
    expect($restoredTeacher->trashed())->toBeFalse();
    expect(User::find($userId)->is_active)->toBeTrue();
    expect($restoredTeacher->classrooms()->count())->toBe(1);
});

test('the archived teachers filter lists soft deleted accounts with a restore action', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($this->admin)->delete(route('teachers.destroy', $teacher));

    $this->actingAs($this->admin)
        ->get(route('teachers.index', ['statut_compte' => 'archived']))
        ->assertOk()
        ->assertSee($teacher->matricule)
        ->assertSee('Restaurer');
});

test('a multi word surname is not truncated when editing a teacher (H1)', function () {
    $teacher = Teacher::factory()->create();
    $teacher->user->update(['name' => 'El Hadji Diop Amadou', 'prenom' => 'Amadou']);

    $editPage = $this->actingAs($this->admin)->get(route('teachers.edit', $teacher));
    $editPage->assertOk()->assertSee('value="El Hadji Diop"', false);
});

test('the email of an archived teacher can be reused for a new teacher account (H2)', function () {
    $teacher = Teacher::factory()->create();
    $teacher->user->update(['email' => 'reuse-teacher@edumanager.sn']);
    $this->actingAs($this->admin)->delete(route('teachers.destroy', $teacher));

    $this->actingAs($this->admin)
        ->post(route('teachers.store'), [
            'nom' => 'Nouveau',
            'prenom' => 'Professeur',
            'email' => 'reuse-teacher@edumanager.sn',
            'date_naissance' => '1990-01-01',
            'lieu_naissance' => 'Dakar',
            'sexe' => 'masculin',
            'nationalite' => 'Sénégalaise',
            'diplomes' => 'Licence',
            'etablissements_formation' => 'UCAD',
            'statut' => 'contractuel',
            'date_recrutement' => '2024-01-01',
            'specialites' => 'Français',
            'filiation' => 'Fils de M. X',
            'contact_urgence_nom' => 'M. X',
            'contact_urgence_tel' => '770000002',
        ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('teachers.index'));

    expect(User::where('email', 'reuse-teacher@edumanager.sn')->whereNull('deleted_at')->exists())->toBeTrue();
});

test('the classe/matiere filter finds a teacher by subject name through pedagogical assignments (H3)', function () {
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create(['school_year_id' => $schoolYear->id, 'name' => 'CM2 A']);
    $matiere = Matiere::factory()->create(['nom' => 'Mathématiques']);
    $teacher = Teacher::factory()->create();

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'matiere_id' => $matiere->id,
        'school_year_id' => $schoolYear->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('teachers.index', ['matiere' => 'Mathématiques']))
        ->assertOk()
        ->assertSee($teacher->matricule);
});

test('enrolling a teacher generates a random temporary password, not the literal "password" (bonus fix found alongside H1/H2)', function () {
    $this->actingAs($this->admin)->post(route('teachers.store'), [
        'nom' => 'Nouveau',
        'prenom' => 'Professeur',
        'email' => 'random-password-teacher@edumanager.sn',
        'date_naissance' => '1990-01-01',
        'lieu_naissance' => 'Dakar',
        'sexe' => 'masculin',
        'nationalite' => 'Sénégalaise',
        'diplomes' => 'Licence',
        'etablissements_formation' => 'UCAD',
        'statut' => 'contractuel',
        'date_recrutement' => '2024-01-01',
        'specialites' => 'Français',
        'filiation' => 'Fils de M. X',
        'contact_urgence_nom' => 'M. X',
        'contact_urgence_tel' => '770000003',
    ]);

    $user = User::where('email', 'random-password-teacher@edumanager.sn')->firstOrFail();
    expect(Hash::check('password', $user->password))->toBeFalse();
    expect($user->password_must_change)->toBeTrue();
});
