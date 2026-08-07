<?php

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;

function createAccountingFixture(): array
{
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
        'matricule' => 'EDU-26-000300',
        'status' => 'active',
    ]);

    return [$schoolYear, $classroom, $student, $registration];
}

test('cancelled payments are excluded from the accounting dashboard totals', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , , $registration] = createAccountingFixture();

    $goodPayment = Payment::create([
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

    $cancelledPayment = Payment::create([
        'registration_id' => $registration->id,
        'amount' => 50000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Novembre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);
    $cancelledPayment->cancel($manager->id, 'Erreur de saisie');

    $response = $this->actingAs($manager)->get(route('accounting.dashboard'));

    $response->assertOk();
    $stats = $response->viewData('stats');

    expect((float) $stats['total_revenue'])->toBe(15000.0);
    expect($stats['total_payments'])->toBe(1);
    expect($stats['complete_payments'])->toBe(1);
});

test('partial payments count as real revenue on the dashboard and reports, not just complete payments', function () {
    // Un paiement partiel encaisse déjà de l'argent réel (le champ amount est bien
    // reçu, seul le solde n'est pas soldé) : il doit compter dans "Revenu total" au
    // même titre qu'un paiement complet, sous peine de sous-évaluer l'encaissement réel.
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , , $registration] = createAccountingFixture();

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 20000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);
    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 7000,
        'status' => 'partiel',
        'remaining_balance' => 8000,
        'month' => 'Novembre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);
    // Un paiement rejeté ne doit jamais compter comme revenu.
    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 99999,
        'status' => 'rejected',
        'remaining_balance' => 0,
        'month' => 'Decembre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    $dashboard = $this->actingAs($manager)->get(route('accounting.dashboard'));
    $stats = $dashboard->viewData('stats');
    expect((float) $stats['total_revenue'])->toBe(27000.0);
    expect((float) $stats['monthly_revenue'])->toBe(27000.0);

    $cashFlow = $this->actingAs($manager)->get(route('accounting.cash-flow'));
    expect((float) $cashFlow->viewData('monthlyInflow'))->toBe(27000.0);

    $reports = $this->actingAs($manager)->get(route('accounting.reports'));
    $classReport = $reports->viewData('classReport');
    expect((float) $classReport->first()['total'])->toBe(27000.0);
});

test('remaining balance on the dashboard matches FeeService, not a naive sum of remaining_balance across payments', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , , $registration] = createAccountingFixture();

    // Deux paiements partiels successifs sur la même mensualité : sommer la colonne
    // remaining_balance des deux lignes donnerait 5000 + 0 = 5000, un chiffre qui ne
    // correspond à rien de réel. Le dashboard doit refléter exactement FeeService.
    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 10000,
        'status' => 'partiel',
        'remaining_balance' => 5000,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'fee_breakdown' => [[
            'id' => 'mensualite-Octobre',
            'code' => 'mensualite',
            'month' => 'Octobre',
            'amount' => 15000,
            'amount_paid' => 10000,
            'remaining_balance' => 5000,
        ]],
        'validated_by' => $manager->id,
    ]);

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 5000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'fee_breakdown' => [[
            'id' => 'mensualite-Octobre',
            'code' => 'mensualite',
            'month' => 'Octobre',
            'amount' => 15000,
            'amount_paid' => 5000,
            'remaining_balance' => 0,
        ]],
        'validated_by' => $manager->id,
    ]);

    $expectedRemaining = app(\App\Services\FeeService::class)->getFinancialSituation($registration)['remaining'];

    $response = $this->actingAs($manager)->get(route('accounting.dashboard'));

    $response->assertOk();
    $stats = $response->viewData('stats');

    expect((float) $stats['remaining_balance'])->toBe((float) $expectedRemaining);
    // La somme naïve des colonnes remaining_balance (5000) ne doit jamais être utilisée telle quelle.
    expect((float) $stats['remaining_balance'])->not->toBe(5000.0);
});

test('parent dashboard remaining balance is not the naive sum of remaining_balance across payments', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    [, , $student, $registration] = createAccountingFixture();
    $student->assignRole('eleve');

    $parentUser = User::factory()->create(['role' => 'parent']);
    $parentUser->assignRole('parent');
    $parent = ParentModel::factory()->create(['user_id' => $parentUser->id]);
    $student->parents()->attach($parent->id, [
        'lien_parente' => 'Pere',
        'est_responsable_financier' => true,
        'est_contact_urgence' => true,
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
        'fee_breakdown' => [[
            'id' => 'mensualite-Octobre', 'code' => 'mensualite', 'month' => 'Octobre',
            'amount' => 15000, 'amount_paid' => 10000, 'remaining_balance' => 5000,
        ]],
    ]);

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 5000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'fee_breakdown' => [[
            'id' => 'mensualite-Octobre', 'code' => 'mensualite', 'month' => 'Octobre',
            'amount' => 15000, 'amount_paid' => 5000, 'remaining_balance' => 0,
        ]],
    ]);

    $expectedRemaining = app(\App\Services\FeeService::class)->getFinancialSituation($registration)['remaining'];

    $response = $this->actingAs($admin)->get(route('parents.show', $parent));

    $response->assertOk();
    $studentsData = $response->viewData('studentsData');
    $remainingBalance = (float) $studentsData->first()['remainingBalance'];

    expect($remainingBalance)->toBe((float) $expectedRemaining);
    // La somme naïve des colonnes remaining_balance (5000 + 0 = 5000) ne doit jamais apparaître ici.
    expect($remainingBalance)->not->toBe(5000.0);
});
