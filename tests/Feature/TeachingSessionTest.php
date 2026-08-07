<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->user = User::factory()->create(['role' => 'professeur']);
    $this->user->assignRole('professeur');
    $this->teacher = Teacher::factory()->create(['user_id' => $this->user->id]);
    $this->schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $this->assignment = PedagogicalAssignment::create([
        'teacher_id' => $this->teacher->id,
        'classroom_id' => Classroom::factory()->create(['school_year_id' => $this->schoolYear->id])->id,
        'matiere_id' => Matiere::factory()->create()->id,
        'school_year_id' => $this->schoolYear->id,
        'volume_horaire_hebdo' => 4,
    ]);
    $this->actingAs($this->user);
});

test('teacher can point a course and see remaining weekly hours', function () {
    $this->post(route('professeur.teaching-sessions.store'), [
        'pedagogical_assignment_id' => $this->assignment->id,
        'taught_on' => now()->startOfWeek()->addDay()->toDateString(),
        'duration_hours' => 1.5,
        'summary' => 'Révision',
    ])->assertRedirect(route('professeur.teaching-sessions.index'));

    $this->get(route('professeur.teaching-sessions.index'))
        ->assertOk()
        ->assertSee('2.5 h restantes')
        ->assertSee('1.5 h réalisées');
});

test('recording a teaching session can also record attendance for the classroom in the same request', function () {
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $this->assignment->classroom_id,
        'registration_fee_paid' => 0,
        'monthly_fee' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => $this->schoolYear->year_string,
        'school_year_id' => $this->schoolYear->id,
        'matricule' => 'EDU-TEST-0001',
        'status' => 'active',
    ]);

    $taughtOn = now()->startOfWeek()->addDay()->toDateString();

    $this->post(route('professeur.teaching-sessions.store'), [
        'pedagogical_assignment_id' => $this->assignment->id,
        'taught_on' => $taughtOn,
        'duration_hours' => 1,
        'attendances' => [
            $student->id => ['status' => 'absent'],
        ],
    ])->assertRedirect(route('professeur.teaching-sessions.index'));

    $this->assertDatabaseHas('attendances', [
        'user_id' => $student->id,
        'classroom_id' => $this->assignment->classroom_id,
        'date' => $taughtOn.' 00:00:00',
        'status' => 'absent',
    ]);
});

test('recording a teaching session cannot record attendance for a student outside the classroom', function () {
    $outsider = User::factory()->create(['role' => 'eleve']);
    $outsider->assignRole('eleve');

    $this->post(route('professeur.teaching-sessions.store'), [
        'pedagogical_assignment_id' => $this->assignment->id,
        'taught_on' => now()->startOfWeek()->addDay()->toDateString(),
        'duration_hours' => 1,
        'attendances' => [
            $outsider->id => ['status' => 'absent'],
        ],
    ])->assertForbidden();

    $this->assertDatabaseMissing('attendances', ['user_id' => $outsider->id, 'classroom_id' => $this->assignment->classroom_id]);
});
