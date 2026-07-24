<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

test('api search by student matricule returns registration and financial info', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $year = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $year->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['matricule' => 'ELE-API-001', 'role' => 'eleve']);
    $student->assignRole('eleve');
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $year->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-API-001',
        'status' => 'active',
    ]);

    $response = $this->actingAs($manager)->getJson('/api/students/by-matricule/ELE-API-001');

    $response->assertOk()
        ->assertJsonPath('registration_id', $registration->id)
        ->assertJsonPath('user.name', $student->name)
        ->assertJsonPath('matricule', $registration->matricule);
});

test('api search by matricule works with role column only (no spatie role assigned)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $year = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $year->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['matricule' => 'ELE-API-002', 'role' => 'eleve']);
    // Do NOT assign Spatie role
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $year->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-API-002',
        'status' => 'active',
    ]);

    $response = $this->actingAs($manager)->getJson('/api/students/by-matricule/ELE-API-002');

    $response->assertOk()
        ->assertJsonPath('registration_id', $registration->id)
        ->assertJsonPath('user.name', $student->name)
        ->assertJsonPath('matricule', $registration->matricule);
});
