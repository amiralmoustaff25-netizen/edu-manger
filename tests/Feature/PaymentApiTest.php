<?php

use App\Models\Classroom;
use App\Models\ParentModel;
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

test('api search by matricule does not expose the student personal data or parents to accounting staff', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $year = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $year->id, 'cycle' => 'primaire']);
    $student = User::factory()->create([
        'matricule' => 'ELE-API-003',
        'role' => 'eleve',
        'medical_notes' => 'Allergie grave aux arachides',
        'emergency_contact_phone' => '771234567',
        'adresse' => 'Rue 12, Dakar',
    ]);
    $student->assignRole('eleve');
    $parent = ParentModel::factory()->create(['statut' => 'actif']);
    $parent->students()->attach($student->id, ['lien_parente' => 'Pere']);
    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $year->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-API-003',
        'status' => 'active',
    ]);

    $response = $this->actingAs($manager)->getJson('/api/students/by-matricule/ELE-API-003');

    $response->assertOk();
    $payload = $response->json();

    expect($payload['user'])->toBe(['name' => $student->name])
        ->and($payload)->not->toHaveKey('parents')
        ->and($payload)->not->toHaveKey('payments')
        ->and($payload)->not->toHaveKey('options')
        ->and(json_encode($payload))->not->toContain('Allergie grave aux arachides')
        ->and(json_encode($payload))->not->toContain('771234567')
        ->and(json_encode($payload))->not->toContain('Rue 12, Dakar');
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
