<?php

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

function createValidatedPaymentFixture(string $status = 'complet'): array
{
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
        'matricule' => 'EDU-26-000200',
        'status' => 'active',
    ]);

    $payment = Payment::create([
        'registration_id' => $registration->id,
        'amount' => 15000,
        'status' => $status,
        'remaining_balance' => 0,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $status === 'complet' ? null : null,
        'validated_at' => $status === 'partiel' ? now() : null,
    ]);

    return [$registration, $payment];
}

test('manager comptable cannot hard delete a validated payment', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, $payment] = createValidatedPaymentFixture('complet');

    $response = $this->actingAs($manager)->delete(route('payments.destroy', $payment));

    $response->assertForbidden();
    $this->assertDatabaseHas('payments', ['id' => $payment->id]);
});

test('manager comptable can cancel a validated payment with a reason and it is audit logged', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, $payment] = createValidatedPaymentFixture('complet');

    $response = $this->actingAs($manager)->patch(route('payments.cancel', $payment), [
        'cancellation_reason' => 'Erreur de saisie du montant',
    ]);

    $response->assertRedirect();
    $payment->refresh();

    expect($payment->isCancelled())->toBeTrue();
    expect($payment->cancelled_by)->toBe($manager->id);
    expect($payment->cancellation_reason)->toBe('Erreur de saisie du montant');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'cancelled',
        'model_type' => Payment::class,
        'model_id' => $payment->id,
        'user_id' => $manager->id,
    ]);
});

test('cancelling a payment requires a reason', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, $payment] = createValidatedPaymentFixture('complet');

    $response = $this->actingAs($manager)->patch(route('payments.cancel', $payment), []);

    $response->assertSessionHasErrors('cancellation_reason');
    expect($payment->refresh()->isCancelled())->toBeFalse();
});

test('a plain comptable cannot cancel a payment', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    [, $payment] = createValidatedPaymentFixture('complet');

    $response = $this->actingAs($comptable)->patch(route('payments.cancel', $payment), [
        'cancellation_reason' => 'Test',
    ]);

    $response->assertForbidden();
});

test('an admin cannot cancel a payment without explicit accounting authorization', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    [, $payment] = createValidatedPaymentFixture('complet');

    $response = $this->actingAs($admin)->patch(route('payments.cancel', $payment), [
        'cancellation_reason' => 'Test',
    ]);

    $response->assertForbidden();
});

test('a cancelled payment is excluded from the financial situation totals', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [$registration, $payment] = createValidatedPaymentFixture('complet');

    $before = app(App\Services\FeeService::class)->getFinancialSituation($registration->fresh());
    expect($before['paid'])->toBe(15000.0);

    $payment->cancel($manager->id, 'Annulation test');

    $after = app(App\Services\FeeService::class)->getFinancialSituation($registration->fresh());
    expect($after['paid'])->toBe(0.0);
});

test('a non validated partial payment can still be deleted normally', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, $payment] = createValidatedPaymentFixture('partiel');
    $payment->update(['validated_at' => null, 'status' => 'partiel']);

    $response = $this->actingAs($manager)->delete(route('payments.destroy', $payment));

    $response->assertRedirect();
    $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
});
