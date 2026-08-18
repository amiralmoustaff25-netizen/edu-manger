<?php

use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

test('first payment activates a pending registration and inactive student account', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve', 'is_active' => false]);
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000099',
        'status' => 'pending',
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
    expect($registration->refresh()->status)->toBe('active');
    expect($student->refresh()->is_active)->toBeTrue();
});

test('an overpayment with change given records only the amount actually kept as revenue, not the amount tendered', function () {
    // Mode "change" (rendre la monnaie), le défaut de config('edu.overpayment_mode') :
    // si le payeur tend 20000 pour une mensualité de 15000, l'établissement ne garde
    // que 15000 — les 5000 rendus ne doivent jamais compter comme revenu encaissé.
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
        'matricule' => 'EDU-26-000160',
        'status' => 'active',
    ]);

    $response = $this->actingAs($manager)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 20000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
        ]);

    $response->assertRedirect();

    $payment = Payment::first();
    expect((float) $payment->amount)->toBe(15000.0);
    expect((float) $payment->remaining_balance)->toBe(0.0);
    expect($payment->status)->toBe('complet');
    expect($payment->comment)->toContain('Monnaie rendue');
    expect($payment->comment)->toContain('5,000.00');
});

test('an overpayment credited to the student keeps the full amount tendered as revenue', function () {
    // Mode "credit" : le surplus reste la propriété de l'établissement (crédité à
    // l'élève pour un usage futur), donc le montant total reçu est bien conservé.
    config(['edu.overpayment_mode' => 'credit']);

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
        'matricule' => 'EDU-26-000161',
        'status' => 'active',
    ]);

    $response = $this->actingAs($manager)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 20000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
        ]);

    $response->assertRedirect();

    $payment = Payment::first();
    expect((float) $payment->amount)->toBe(20000.0);
    expect(\App\Models\Credit::where('payment_id', $payment->id)->sum('amount'))->toEqual(5000);
});

test('a user without valider-paiement-partiel can still register a partial payment, pending validation', function () {
    // Un comptable peut avoir 'valider-paiement-partiel' révoqué individuellement (cf.
    // RoleAssignmentController), tout en gardant 'enregistrer-paiement'. Un paiement partiel
    // doit tout de même être créé (en attente de validation par un manager), jamais bloqué par un 403.
    $comptableWithoutValidation = User::factory()->create();
    $comptableWithoutValidation->assignRole('comptable');
    // Révocation individuelle via le mécanisme réel de l'app (UserPermissionOverride),
    // pas Spatie::revokePermissionTo() qui ne retire pas une permission héritée du rôle.
    $comptableWithoutValidation->permissionOverrides()->create([
        'permission_id' => \Spatie\Permission\Models\Permission::findByName('valider-paiement-partiel')->id,
        'type' => 'revoke',
    ]);
    $comptableWithoutValidation->forgetPermissionCache();

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
        'matricule' => 'EDU-26-000150',
        'status' => 'active',
    ]);

    $response = $this->actingAs($comptableWithoutValidation)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 10000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('payments', [
        'registration_id' => $registration->id,
        'amount' => 10000,
        'status' => 'partiel',
        'validated_by' => null,
    ]);
});

test('comptable cannot re-select an already fully paid fee', function () {
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
        'registration_fee_paid' => 5000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000100',
        'status' => 'active',
    ]);

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 5000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Inscription',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'inscription',
        'fee_breakdown' => [[
            'id' => 'inscription',
            'code' => 'inscription',
            'description' => "Frais d'inscription",
            'month' => null,
            'amount' => 5000,
            'amount_paid' => 5000,
            'remaining_amount' => 0,
        ]],
        'validated_by' => $comptable->id,
    ]);

    $response = $this->actingAs($comptable)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 5000,
            'month' => 'Inscription',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
            'selected_fees' => json_encode([[
                'id' => 'inscription',
                'code' => 'inscription',
                'description' => "Frais d'inscription",
                'month' => null,
                'amount' => 5000,
            ]]),
        ]);

    $response->assertSessionHasErrors('selected_fees');
    $this->assertDatabaseCount('payments', 1);
});

test('manager comptable can override and re-select an already fully paid fee', function () {
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
        'registration_fee_paid' => 5000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000101',
        'status' => 'active',
    ]);

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 5000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Inscription',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'inscription',
        'fee_breakdown' => [[
            'id' => 'inscription',
            'code' => 'inscription',
            'description' => "Frais d'inscription",
            'month' => null,
            'amount' => 5000,
            'amount_paid' => 5000,
            'remaining_amount' => 0,
        ]],
        'validated_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 5000,
            'month' => 'Inscription',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
            'selected_fees' => json_encode([[
                'id' => 'inscription',
                'code' => 'inscription',
                'description' => "Frais d'inscription",
                'month' => null,
                'amount' => 5000,
            ]]),
        ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('payments', 2);
});

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

test('comptable can store a partial payment', function () {
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

    $response->assertRedirect();
    $this->assertDatabaseHas('payments', [
        'registration_id' => $registration->id,
        'amount' => 10000,
        'status' => 'partiel',
    ]);
});
