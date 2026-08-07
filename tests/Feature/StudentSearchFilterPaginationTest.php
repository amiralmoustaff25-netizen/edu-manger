<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEnrolledStudent(SchoolYear $year, Classroom $classroom, array $attrs = [], string $status = 'active'): User
{
    $student = User::factory()->create(array_merge(['role' => 'eleve'], $attrs));
    $student->assignRole('eleve');
    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 0,
        'monthly_fee' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-'.$student->id,
        'status' => $status,
    ]);

    return $student;
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->admin->assignRole('admin');
    $this->year = SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true]);
    $this->classroomA = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $this->year->id]);
    $this->classroomB = Classroom::create(['name' => 'CM2 B', 'cycle' => 'primaire', 'school_year_id' => $this->year->id]);
});

test('the classroom filter only shows students of the selected classroom', function () {
    $inA = makeEnrolledStudent($this->year, $this->classroomA, ['name' => 'Élève Classe A']);
    $inB = makeEnrolledStudent($this->year, $this->classroomB, ['name' => 'Élève Classe B']);

    $response = $this->actingAs($this->admin)->get(route('students.index', ['classroom_id' => $this->classroomA->id]));

    $response->assertOk()->assertSee('Élève Classe A')->assertDontSee('Élève Classe B');
});

test('the status filter distinguishes registration status (active/pending) from account activation', function () {
    // Le filtre "status" recoupe deux notions indépendantes du code : le statut de
    // l'inscription (active/pending, sur Registration) et l'activation du compte
    // (is_active, sur User) — un élève peut avoir is_active=false tout en gardant
    // une inscription au statut "active" (ex. compte suspendu sans regénérer
    // l'inscription). Le filtre "inactive" ne regarde donc QUE is_active.
    $activeRegistration = makeEnrolledStudent($this->year, $this->classroomA, ['name' => 'Élève Actif', 'is_active' => true], 'active');
    $pendingRegistration = makeEnrolledStudent($this->year, $this->classroomA, ['name' => 'Élève En Attente', 'is_active' => true], 'pending');
    $deactivatedAccount = makeEnrolledStudent($this->year, $this->classroomA, ['name' => 'Compte Désactivé', 'is_active' => false], 'active');

    $this->actingAs($this->admin)->get(route('students.index', ['status' => 'active']))
        ->assertOk()->assertSee('Élève Actif')->assertDontSee('Élève En Attente');

    $this->actingAs($this->admin)->get(route('students.index', ['status' => 'pending']))
        ->assertOk()->assertSee('Élève En Attente')->assertDontSee('Élève Actif');

    $this->actingAs($this->admin)->get(route('students.index', ['status' => 'inactive']))
        ->assertOk()->assertSee('Compte Désactivé')->assertDontSee('Élève En Attente');
});

test('the students listing paginates at 10 per page', function () {
    for ($i = 0; $i < 15; $i++) {
        makeEnrolledStudent($this->year, $this->classroomA);
    }

    $page1 = $this->actingAs($this->admin)->get(route('students.index'));
    $page1->assertOk();
    expect($page1->viewData('students')->count())->toBe(10);
    expect($page1->viewData('students')->total())->toBeGreaterThanOrEqual(15);

    $page2 = $this->actingAs($this->admin)->get(route('students.index', ['page' => 2]));
    expect($page2->viewData('students')->currentPage())->toBe(2);
});
