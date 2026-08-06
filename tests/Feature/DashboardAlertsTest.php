<?php

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('dashboard alerts reflect real partial payments and are not tied to the unused invoice module', function () {
    $manager = User::factory()->create(['is_active' => true]);
    $manager->assignRole('manager-comptable');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true, 'status' => 'active']);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve']);
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000400',
        'status' => 'active',
    ]);

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 10000,
        'status' => 'partiel',
        'remaining_balance' => 5000,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
    ]);

    $cancelledPartial = Payment::create([
        'registration_id' => $registration->id,
        'amount' => 8000,
        'status' => 'partiel',
        'remaining_balance' => 7000,
        'month' => 'Novembre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
    ]);
    $cancelledPartial->cancel($manager->id, 'Erreur de saisie');

    $response = $this->actingAs($manager)->get(route('dashboard'));

    $response->assertOk();
    $alerts = $response->viewData('alerts');
    $stats = $response->viewData('stats');

    expect($alerts['partial_payments'])->toBe(1);
    expect($stats['partial_payments'])->toBe(1);

    $expectedRemaining = app(\App\Services\FeeService::class)->getFinancialSituation($registration)['remaining'];
    expect((float) $stats['remaining_balance'])->toBe((float) $expectedRemaining);
});
