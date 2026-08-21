<?php

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\FeeService;
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

    $expectedRemaining = app(FeeService::class)->getFinancialSituation($registration)['remaining'];
    expect((float) $stats['remaining_balance'])->toBe((float) $expectedRemaining);
});

test('the main dashboard financial stats only reflect the active school year, not stale years', function () {
    // Même défaut que celui déjà corrigé sur le tableau de bord Comptabilité
    // (AccountingController::index) : un paiement d'une inscription rattachée à une
    // année scolaire déjà clôturée ne doit pas gonfler "encaissé ce mois-ci",
    // "paiements partiels" ou "revenu du mois" sur ce tableau de bord général.
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
        'matricule' => 'EDU-26-000401',
        'status' => 'active',
    ]);

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 15000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    $oldYear = SchoolYear::create(['year_string' => '2024-2025', 'is_active' => false, 'status' => 'closed']);
    $oldClassroom = Classroom::create(['name' => 'CE2 A', 'school_year_id' => $oldYear->id, 'cycle' => 'primaire']);
    $oldStudent = User::factory()->create(['role' => 'eleve']);
    $oldRegistration = Registration::create([
        'user_id' => $oldStudent->id,
        'classroom_id' => $oldClassroom->id,
        'school_year_id' => $oldYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2024-2025',
        'matricule' => 'EDU-25-000402',
        'status' => 'active',
    ]);
    Payment::create([
        'registration_id' => $oldRegistration->id,
        'amount' => 99999,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);
    $oldPartial = Payment::create([
        'registration_id' => $oldRegistration->id,
        'amount' => 5000,
        'status' => 'partiel',
        'remaining_balance' => 10000,
        'month' => 'Novembre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)->get(route('dashboard'));
    $response->assertOk();
    $stats = $response->viewData('stats');
    $alerts = $response->viewData('alerts');

    expect($stats['paid_this_month'])->toBe(1);
    expect((float) $stats['monthly_revenue'])->toBe(15000.0);
    expect($stats['partial_payments'])->toBe(0);
    expect($alerts['partial_payments'])->toBe(0);
});
