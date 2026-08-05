<?php

use App\Models\Classroom;
use App\Models\ClassroomFee;
use App\Models\Discount;
use App\Models\FeeType;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\FeeService;

function makeRegistrationWithMensualite(): Registration
{
    $schoolYear = SchoolYear::create([
        'year_string' => '2025-2026',
        'is_active' => true,
        'start_date' => '2025-09-01',
        'end_date' => '2026-06-30',
    ]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $feeType = FeeType::create([
        'name' => 'Scolarité mensuelle',
        'code' => 'mensualite',
        'is_recurring' => true,
        'is_optional' => false,
    ]);

    ClassroomFee::create([
        'classroom_id' => $classroom->id,
        'fee_type_id' => $feeType->id,
        'school_year_id' => $schoolYear->id,
        'amount' => 20000,
        'version' => 1,
        'is_current' => true,
    ]);

    $student = User::factory()->create(['role' => 'eleve']);

    return Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 20000,
        'registration_fee_paid' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000200',
        'status' => 'active',
    ]);
}

test('a plain comptable cannot grant a tariff derogation', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    $registration = makeRegistrationWithMensualite();

    $response = $this->actingAs($comptable)->post("/registrations/{$registration->id}/discounts", [
        'name' => 'Bourse',
        'type' => 'percentage',
        'value' => 50,
        'reason' => 'Situation familiale difficile',
    ]);

    $response->assertForbidden();
    expect(Discount::count())->toBe(0);
});

test('a manager comptable can grant a tariff derogation and it reduces the monthly fee', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $registration = makeRegistrationWithMensualite();

    $response = $this->actingAs($manager)->post("/registrations/{$registration->id}/discounts", [
        'name' => 'Bourse',
        'type' => 'percentage',
        'value' => 50,
        'reason' => 'Situation familiale difficile',
    ]);

    $response->assertRedirect();

    $discount = Discount::first();
    expect($discount->applied_by)->toBe($manager->id);

    $fees = app(FeeService::class)->getPendingFees($registration->refresh());
    $mensualite = collect($fees)->firstWhere('code', 'mensualite');

    expect($mensualite['amount'])->toEqual(10000.0);
});

test('a manager comptable can revoke a tariff derogation', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $registration = makeRegistrationWithMensualite();

    $discount = $registration->discounts()->create([
        'name' => 'Bourse',
        'type' => 'fixed',
        'value' => 5000,
        'reason' => 'Aide ponctuelle',
        'applied_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)->delete("/discounts/{$discount->id}");

    $response->assertRedirect();
    expect(Discount::count())->toBe(0);
});
