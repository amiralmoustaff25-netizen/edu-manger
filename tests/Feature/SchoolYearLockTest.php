<?php

use App\Models\Classroom;
use App\Models\ClassroomFee;
use App\Models\FeeType;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Support\SchoolYearStatus;

function createLockedYearFixture(): array
{
    $lockedYear = SchoolYear::create([
        'year_string' => '2024-2025',
        'is_active' => false,
        'status' => SchoolYearStatus::CLOSED,
    ]);

    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $lockedYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve']);
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $lockedYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2024-2025',
        'matricule' => 'EDU-25-000300',
        'status' => 'active',
    ]);

    return [$lockedYear, $classroom, $registration];
}

test('manager comptable cannot register a payment on a locked school year', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , $registration] = createLockedYearFixture();

    $response = $this->actingAs($manager)->post('/payments', [
        'registration_id' => $registration->id,
        'amount_paid' => 15000,
        'month' => 'Octobre',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'espèces',
    ]);

    $response->assertSessionHasErrors('school_year');
    $this->assertDatabaseCount('payments', 0);
});

test('super admin can override the lock and register a payment on a locked school year', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    [, , $registration] = createLockedYearFixture();

    $response = $this->actingAs($superAdmin)->post('/payments', [
        'registration_id' => $registration->id,
        'amount_paid' => 15000,
        'month' => 'Octobre',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'espèces',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('payments', 1);
});

test('manager comptable cannot modify a tariff on a locked school year', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [$lockedYear, $classroom] = createLockedYearFixture();
    $feeType = FeeType::create(['name' => 'Mensualité', 'code' => 'mensualite']);

    $classroomFee = ClassroomFee::create([
        'classroom_id' => $classroom->id,
        'fee_type_id' => $feeType->id,
        'school_year_id' => $lockedYear->id,
        'amount' => 15000,
        'version' => 1,
        'is_current' => true,
        'created_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)->put(route('classroom-fees.update', $classroomFee), [
        'classroom_id' => $classroom->id,
        'fee_type_id' => $feeType->id,
        'school_year_id' => $lockedYear->id,
        'amount' => 20000,
    ]);

    $response->assertSessionHasErrors('school_year');
    $this->assertDatabaseCount('classroom_fees', 1);
});

test('manager comptable cannot grant or revoke a tariff derogation on a locked school year', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , $registration] = createLockedYearFixture();

    $response = $this->actingAs($manager)->post(route('discounts.store', $registration), [
        'name' => 'Bourse tardive',
        'type' => 'fixed',
        'value' => 5000,
        'reason' => 'Tentative sur année clôturée',
    ]);

    $response->assertSessionHasErrors('school_year');
    $this->assertDatabaseCount('discounts', 0);

    $discount = $registration->discounts()->create([
        'name' => 'Existante',
        'type' => 'fixed',
        'value' => 5000,
        'reason' => 'Créée avant clôture',
        'applied_by' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->delete(route('discounts.destroy', $discount))
        ->assertSessionHasErrors('school_year');

    $this->assertDatabaseHas('discounts', ['id' => $discount->id]);
});

test('active school year is not locked', function () {
    $activeYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true, 'status' => SchoolYearStatus::ACTIVE]);

    expect($activeYear->isLocked())->toBeFalse();
});
