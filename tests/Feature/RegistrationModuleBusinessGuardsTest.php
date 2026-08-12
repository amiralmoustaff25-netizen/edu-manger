<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->admin->assignRole('admin');

    $this->schoolYear = SchoolYear::factory()->create([
        'status' => 'active',
        'is_active' => true,
        'year_string' => '2025-2026',
    ]);
    $this->classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
});

test('the reinscription button on a student profile links to the reinscription form, not a new registration (H1)', function () {
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $this->classroom->id,
        'registration_fee_paid' => 5000,
        'monthly_fee' => 15000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $this->schoolYear->year_string,
        'school_year_id' => $this->schoolYear->id,
        'matricule' => 'EDU-TEST-0001',
        'status' => 'active',
    ]);

    // La sidebar contient légitimement un lien vers registrations.create (module
    // "Nouvelle inscription") : seul le bouton "🔁 Réinscription" de cette page est
    // sous test, donc on vérifie sa présence plutôt que l'absence globale du lien.
    $this->actingAs($this->admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertSee(route('registrations.reinscription', ['matricule' => $student->matricule]), false);
});

test('the database rejects two registrations for the same student and school year (H3)', function () {
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');

    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $this->classroom->id,
        'registration_fee_paid' => 5000,
        'monthly_fee' => 15000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $this->schoolYear->year_string,
        'school_year_id' => $this->schoolYear->id,
        'matricule' => 'EDU-TEST-0002',
        'status' => 'active',
    ]);

    expect(fn () => Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $this->classroom->id,
        'registration_fee_paid' => 5000,
        'monthly_fee' => 15000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $this->schoolYear->year_string,
        'school_year_id' => $this->schoolYear->id,
        'matricule' => 'EDU-TEST-0003',
        'status' => 'pending',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('reenrolling a non student account is rejected (H4)', function () {
    $notAStudent = User::factory()->create(['role' => 'comptable']);
    $notAStudent->assignRole('comptable');

    $this->actingAs($this->admin)
        ->post(route('registrations.reinscription.store'), [
            'user_id' => $notAStudent->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ])
        ->assertSessionHasErrors('user_id');

    expect(Registration::where('user_id', $notAStudent->id)->exists())->toBeFalse();
});

test('reenrolling a boarding student keeps the internat option instead of silently dropping it (H5)', function () {
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    $previousYear = SchoolYear::factory()->create(['is_active' => false, 'year_string' => '2024-2025']);
    $previousClassroom = Classroom::factory()->create(['school_year_id' => $previousYear->id]);

    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $previousClassroom->id,
        'registration_fee_paid' => 5000,
        'monthly_fee' => 15000,
        'options' => ['cantine' => false, 'transport' => false, 'internat' => true],
        'registration_date' => now()->subYear()->toDateString(),
        'academic_year' => $previousYear->year_string,
        'school_year_id' => $previousYear->id,
        'matricule' => 'EDU-TEST-0004',
        'status' => 'graduated',
    ]);

    $reinscriptionPage = $this->actingAs($this->admin)
        ->get(route('registrations.reinscription', ['matricule' => $student->matricule]));
    $reinscriptionPage->assertOk()->assertSee('checked', false);

    $this->actingAs($this->admin)
        ->post(route('registrations.reinscription.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
            'options' => ['cantine' => '0', 'transport' => '0', 'internat' => '1'],
        ])
        ->assertRedirect(route('dashboard'));

    $newRegistration = Registration::where('user_id', $student->id)->where('school_year_id', $this->schoolYear->id)->firstOrFail();
    expect($newRegistration->options['internat'] ?? false)->toBeTrue();
});

test('a role without creer-inscription cannot access the registration or reenrollment forms (M1)', function () {
    $comptable = User::factory()->create(['role' => 'comptable', 'is_active' => true]);
    $comptable->assignRole('comptable');

    $this->actingAs($comptable)->get(route('registrations.create'))->assertForbidden();
    $this->actingAs($comptable)->get(route('registrations.reinscription'))->assertForbidden();
});

test('the email of an archived student can be reused for a new registration (M3)', function () {
    $archived = User::factory()->create(['role' => 'eleve', 'email' => 'reuse-student@edumanager.sn']);
    $archived->assignRole('eleve');
    $archived->delete();

    $this->actingAs($this->admin)
        ->post(route('registrations.store'), [
            'nom' => 'Nouveau',
            'prenom' => 'Eleve',
            'email' => 'reuse-student@edumanager.sn',
            'date_naissance' => '2015-01-01',
            'lieu_naissance' => 'Dakar',
            'sexe' => 'M',
            'cycle' => 'primaire',
            'classroom_id' => $this->classroom->id,
            'is_active' => '1',
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('dashboard'));

    expect(User::where('email', 'reuse-student@edumanager.sn')->whereNull('deleted_at')->exists())->toBeTrue();
});

test('searching for an archived student matricule explains that it must be restored first, instead of "not found" (F1)', function () {
    $archived = User::factory()->create(['role' => 'eleve', 'matricule' => 'ELE-ARCHIVED-01']);
    $archived->assignRole('eleve');
    $archived->delete();

    $this->actingAs($this->admin)
        ->get(route('registrations.reinscription', ['matricule' => 'ELE-ARCHIVED-01']))
        ->assertOk()
        ->assertSee('archivé')
        ->assertDontSee('Aucun élève trouvé');
});
