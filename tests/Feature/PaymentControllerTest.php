<?php

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

test('manager comptable can store a payment', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve']);
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000001',
        'status' => 'active',
    ]);

    $response = $this->actingAs($manager)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 15000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('payments', [
        'registration_id' => $registration->id,
        'amount' => 15000,
        'status' => 'complet',
    ]);
});

test('comptable can store a complete payment', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve']);
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000002',
        'status' => 'active',
    ]);

    $response = $this->actingAs($comptable)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 15000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('payments', [
        'registration_id' => $registration->id,
        'amount' => 15000,
        'status' => 'complet',
    ]);
});

test('comptable cannot store a partial payment', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve']);
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000003',
        'status' => 'active',
    ]);

    $response = $this->actingAs($comptable)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 10000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
        ]);

    $response->assertForbidden();
});
