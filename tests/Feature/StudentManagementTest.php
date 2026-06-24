<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

function createStudentFixture(): array
{
    $year = SchoolYear::create([
        'year_string' => '2026-2027',
        'is_active' => true,
    ]);

    $classroom = Classroom::create([
        'name' => 'CM2 A',
        'cycle' => 'primaire',
        'school_year_id' => $year->id,
    ]);

    $student = User::factory()->create([
        'matricule' => 'ELE-TEST-001',
        'name' => 'Élève Test',
        'role' => 'eleve',
        'is_active' => true,
    ]);
    $student->assignRole('eleve');

    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 25000,
        'monthly_fee' => 15000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-TEST-001',
        'status' => 'pending',
    ]);

    return [$year, $classroom, $student, $registration];
}

test('admin can view students listing', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student] = createStudentFixture();

    $this->actingAs($admin)
        ->get(route('students.index', ['search' => $student->matricule]))
        ->assertOk()
        ->assertSee('Gestion des élèves')
        ->assertSee($student->name);
});

test('comptable cannot access student management', function () {
    $comptable = User::factory()->create(['role' => 'comptable']);
    $comptable->assignRole('comptable');

    $this->actingAs($comptable)
        ->get(route('students.index'))
        ->assertForbidden();
});

test('admin can view student detail with registration history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student, $registration] = createStudentFixture();

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee($registration->matricule);
});

test('admin can transfer student to another classroom', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [$year, , $student, $registration] = createStudentFixture();

    $newClassroom = Classroom::create([
        'name' => 'CM2 B',
        'cycle' => 'primaire',
        'school_year_id' => $year->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('students.transfer', $student), [
            'registration_id' => $registration->id,
            'classroom_id' => $newClassroom->id,
        ])
        ->assertRedirect();

    expect($registration->refresh()->classroom_id)->toBe($newClassroom->id);
});

test('admin can update student status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [, , $student, $registration] = createStudentFixture();

    $this->actingAs($admin)
        ->patch(route('students.status', $student), [
            'registration_id' => $registration->id,
            'status' => 'active',
        ])
        ->assertRedirect();

    expect($registration->refresh()->status)->toBe('active');
    expect($student->refresh()->is_active)->toBeTrue();
});
