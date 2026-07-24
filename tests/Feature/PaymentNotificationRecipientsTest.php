<?php

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Notifications\PaymentReceived;

it('notifies the student and parents when a payment is recorded', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve', 'name' => 'Amadou Diallo']);
    $student->assignRole('eleve');

    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-NOTIF-001',
        'status' => 'active',
    ]);

    $parentUser = User::factory()->create(['role' => 'parent'])->assignRole('parent');
    $parent = ParentModel::factory()->create(['user_id' => $parentUser->id]);
    $parent->students()->attach($student->id, [
        'lien_parente' => 'Pere',
        'est_responsable_financier' => true,
        'est_contact_urgence' => true,
    ]);

    $this->actingAs($manager)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 15000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $student->id,
        'type' => PaymentReceived::class,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $parentUser->id,
        'type' => PaymentReceived::class,
    ]);
});

it('shows the payment notification in the student notification center', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve'])->assignRole('eleve');

    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 25000,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-NOTIF-002',
        'status' => 'active',
    ]);

    $this->actingAs($manager)
        ->post('/payments', [
            'registration_id' => $registration->id,
            'amount_paid' => 15000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
        ]);

    $this->actingAs($student)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Paiement reçu');
});
