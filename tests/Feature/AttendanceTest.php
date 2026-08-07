<?php

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->teacherUser = User::factory()->create(['role' => 'professeur']);
    $this->teacherUser->assignRole('professeur');
    $this->teacher = Teacher::factory()->create(['user_id' => $this->teacherUser->id]);
    $this->schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $this->classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
    $this->teacher->classrooms()->attach($this->classroom->id, ['annee_scolaire' => $this->schoolYear->year_string, 'matiere_id' => null, 'volume_horaire_hebdo' => 4]);
    $this->student = User::factory()->create(['role' => 'eleve']);
    $this->student->assignRole('eleve');
    Registration::factory()->create(['user_id' => $this->student->id, 'classroom_id' => $this->classroom->id, 'school_year_id' => $this->schoolYear->id, 'status' => 'active']);
    $this->actingAs($this->teacherUser);
});

test('teacher can record attendances without a carbon error', function () {
    $this->post(route('professeur.attendances.store'), [
        'classroom_id' => $this->classroom->id,
        'date' => today()->toDateString(),
        'attendances' => [$this->student->id => ['user_id' => $this->student->id, 'status' => 'present', 'notes' => null]],
    ])->assertRedirect(route('professeur.attendances.index', ['classroom_id' => $this->classroom->id, 'date' => today()->toDateString()]));

    $attendance = Attendance::where('user_id', $this->student->id)->where('classroom_id', $this->classroom->id)->firstOrFail();

    expect($attendance->status)->toBe('present')
        ->and($attendance->date->toDateString())->toBe(today()->toDateString());
});

test('teacher can only read attendance history for assigned classes', function () {
    Attendance::factory()->create(['user_id' => $this->student->id, 'classroom_id' => $this->classroom->id, 'recorded_by' => $this->teacherUser->id]);

    $this->get(route('professeur.attendances.history', ['classroom_id' => $this->classroom->id]))
        ->assertOk()
        ->assertSee('Historique des présences')
        ->assertSee($this->student->name);
});

test('teacher cannot record an attendance for a student not enrolled in the classroom', function () {
    $outsider = User::factory()->create(['role' => 'eleve']);
    $outsider->assignRole('eleve');

    $this->post(route('professeur.attendances.store'), [
        'classroom_id' => $this->classroom->id,
        'date' => today()->toDateString(),
        'attendances' => [$outsider->id => ['user_id' => $outsider->id, 'status' => 'present', 'notes' => null]],
    ])->assertForbidden();

    $this->assertDatabaseMissing('attendances', ['user_id' => $outsider->id, 'classroom_id' => $this->classroom->id]);
});

test('admin can read the school attendance overview', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    Attendance::factory()->create(['user_id' => $this->student->id, 'classroom_id' => $this->classroom->id, 'recorded_by' => $this->teacherUser->id]);

    $this->actingAs($admin)
        ->get(route('attendances.overview'))
        ->assertOk()
        ->assertSee('Présences par classe')
        ->assertSee($this->student->name);
});
