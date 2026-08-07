<?php

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

test('the receipt shows the amount due before this transaction, not amount plus amount_paid double-counted', function () {
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
        'registration_fee_paid' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000500',
        'status' => 'active',
    ]);

    // Une ligne de frais dont le total est 15000, dont 10000 ont été versés lors de
    // CETTE transaction, laissant 5000 restants. Le montant réellement dû avant cette
    // transaction était donc 5000 (10000 + 5000), pas 15000 + 10000 = 25000.
    $payment = Payment::create([
        'registration_id' => $registration->id,
        'amount' => 10000,
        'status' => 'partiel',
        'remaining_balance' => 5000,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
        'fee_breakdown' => [[
            'description' => 'Scolarité mensuelle - Octobre',
            'code' => 'mensualite',
            'amount' => 15000,
            'amount_paid' => 10000,
            'remaining_balance' => 5000,
        ]],
    ]);
    $payment->load('registration.user', 'registration.classroom', 'validatedBy');

    $html = view('accounting.payments.receipt', compact('payment'))->render();

    expect($html)->toContain('10 000 FCFA</td>')
        ->toContain('5 000 FCFA</td>');
    expect($html)->not->toContain('25 000 FCFA');
});
