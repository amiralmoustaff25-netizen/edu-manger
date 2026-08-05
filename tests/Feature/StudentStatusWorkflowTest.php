<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

function createStatusFixture(string $status = 'pending'): array
{
    $year = SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true, 'status' => 'active']);
    $classroom = Classroom::create(['name' => 'CE2 B', 'school_year_id' => $year->id, 'cycle' => 'primaire']);

    $student = User::factory()->create(['role' => 'eleve', 'is_active' => $status === 'active']);
    $student->assignRole('eleve');

    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $year->id,
        'monthly_fee' => 10000,
        'registration_fee_paid' => 20000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'matricule' => 'EDU-STAT-'.rand(1000, 9999),
        'status' => $status,
    ]);

    return [$student, $registration];
}

test('admin can transition a pending registration to active', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [$student, $registration] = createStatusFixture('pending');

    $this->actingAs($admin)
        ->patch(route('students.status', $student), [
            'registration_id' => $registration->id,
            'status' => 'active',
        ])
        ->assertRedirect();

    expect($registration->refresh()->status)->toBe('active');
    expect($student->refresh()->is_active)->toBeTrue();
});

test('invalid status transition is rejected', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [$student, $registration] = createStatusFixture('pending');

    $response = $this->actingAs($admin)
        ->patch(route('students.status', $student), [
            'registration_id' => $registration->id,
            'status' => 'graduated',
        ]);

    $response->assertSessionHasErrors('status');
    expect($registration->refresh()->status)->toBe('pending');
});

test('sensitive transitions require a written reason', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [$student, $registration] = createStatusFixture('active');

    $withoutReason = $this->actingAs($admin)
        ->patch(route('students.status', $student), [
            'registration_id' => $registration->id,
            'status' => 'expelled',
        ]);
    $withoutReason->assertSessionHasErrors('status_reason');
    expect($registration->refresh()->status)->toBe('active');

    $withReason = $this->actingAs($admin)
        ->patch(route('students.status', $student), [
            'registration_id' => $registration->id,
            'status' => 'expelled',
            'status_reason' => 'Comportement violent répété.',
        ]);
    $withReason->assertRedirect();
    expect($registration->refresh()->status)->toBe('expelled');
    expect($student->refresh()->is_active)->toBeFalse();
});

test('terminal statuses cannot transition further', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    [$student, $registration] = createStatusFixture('graduated');

    $response = $this->actingAs($admin)
        ->patch(route('students.status', $student), [
            'registration_id' => $registration->id,
            'status' => 'active',
        ]);

    $response->assertSessionHasErrors('status');
    expect($registration->refresh()->status)->toBe('graduated');
});
