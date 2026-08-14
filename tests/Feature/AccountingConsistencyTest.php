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

    // "Rapports financiers" a été fusionnée dans "Analyse avancée" (voir Checkpoint 12).
    $reports = $this->actingAs($manager)->get(route('accounting.advanced-reports'));
    $classroomBreakdown = $reports->viewData('classroomBreakdown');
    expect((float) $classroomBreakdown->first()['total'])->toBe(27000.0);
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

test('students with debt on the dashboard only reflects the active school year, not stale years', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , , $registration] = createAccountingFixture();

    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 10000,
        'status' => 'partiel',
        'remaining_balance' => 5000,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    // Élève d'une année révolue avec un impayé jamais soldé : ne doit plus
    // apparaître dans la liste "courante" une fois cette année clôturée.
    $oldYear = SchoolYear::create(['year_string' => '2024-2025', 'is_active' => false, 'status' => 'closed']);
    $oldClassroom = Classroom::create(['name' => 'CE2 A', 'school_year_id' => $oldYear->id, 'cycle' => 'primaire']);
    $oldStudent = User::factory()->create(['role' => 'eleve']);
    $oldRegistration = Registration::create([
        'user_id' => $oldStudent->id,
        'classroom_id' => $oldClassroom->id,
        'school_year_id' => $oldYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 0,
        'registration_date' => now()->subYear()->toDateString(),
        'academic_year' => '2024-2025',
        'matricule' => 'EDU-25-000301',
        'status' => 'active',
    ]);
    Payment::create([
        'registration_id' => $oldRegistration->id,
        'amount' => 10000,
        'status' => 'partiel',
        'remaining_balance' => 5000,
        'month' => 'Octobre',
        'payment_date' => now()->subYear(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)->get(route('accounting.dashboard'));
    $response->assertOk();
    $studentsWithDebt = $response->viewData('studentsWithDebt');

    expect($studentsWithDebt->pluck('student.id'))->toContain($registration->user_id);
    expect($studentsWithDebt->pluck('student.id'))->not->toContain($oldStudent->id);
});

test('total revenue on the dashboard only reflects the active school year, not a lifetime cumulative sum', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , , $registration] = createAccountingFixture();

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

    // Paiement d'une année révolue : ne doit plus compter dans le "Revenu total"
    // une fois cette année clôturée et une nouvelle année active.
    $oldYear = SchoolYear::create(['year_string' => '2024-2025', 'is_active' => false, 'status' => 'closed']);
    $oldClassroom = Classroom::create(['name' => 'CE2 A', 'school_year_id' => $oldYear->id, 'cycle' => 'primaire']);
    $oldStudent = User::factory()->create(['role' => 'eleve']);
    $oldRegistration = Registration::create([
        'user_id' => $oldStudent->id,
        'classroom_id' => $oldClassroom->id,
        'school_year_id' => $oldYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 0,
        'registration_date' => now()->subYear()->toDateString(),
        'academic_year' => '2024-2025',
        'matricule' => 'EDU-25-000302',
        'status' => 'active',
    ]);
    Payment::create([
        'registration_id' => $oldRegistration->id,
        'amount' => 99999,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Octobre',
        'payment_date' => now()->subYear(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    $response = $this->actingAs($manager)->get(route('accounting.dashboard'));
    $response->assertOk();
    $stats = $response->viewData('stats');

    expect((float) $stats['total_revenue'])->toBe(15000.0);
});

test('monthly revenue on the dashboard excludes a same-calendar-month payment made under a closed school year', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    [, , , $registration] = createAccountingFixture();

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

    // Encaissement du même mois calendaire, mais sur l'inscription d'une année
    // déjà clôturée (ex. dernier versement avant la clôture) : ne doit pas
    // gonfler "l'encaissement de ce mois" une fois la nouvelle année active.
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
        'matricule' => 'EDU-25-000303',
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

    $response = $this->actingAs($manager)->get(route('accounting.dashboard'));
    $response->assertOk();
    $stats = $response->viewData('stats');

    expect((float) $stats['monthly_revenue'])->toBe(15000.0);
    expect((float) $stats['yearly_revenue'])->toBe(15000.0);
});

test('the monthly revenue chart spans the active school year date range, not a fixed calendar year', function () {
    // Année scolaire chevauchant deux années calendaires (octobre 2025 -> juillet 2026),
    // comme c'est le cas dans la réalité — une boucle janvier->décembre d'une seule année
    // calendaire manquerait les mois d'octobre à décembre 2025.
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $schoolYear = SchoolYear::create([
        'year_string' => '2025-2026',
        'is_active' => true,
        'status' => 'active',
        'start_date' => '2025-10-01',
        'end_date' => '2026-07-20',
    ]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    $student = User::factory()->create(['role' => 'eleve']);
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 0,
        'registration_date' => '2025-10-05',
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000304',
        'status' => 'active',
    ]);

    // Les deux paiements sont d'abord créés normalement (receipt_number auto-généré à
    // partir de created_at="maintenant" pour les deux, donc sans collision), puis
    // rétrodatés ensemble seulement après coup : rétrodater l'un avant de créer l'autre
    // fausserait le comptage utilisé par la génération du numéro de reçu suivant.
    $novemberPayment = Payment::create([
        'registration_id' => $registration->id,
        'amount' => 15000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Novembre',
        'payment_date' => '2025-11-15',
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    $junePayment = Payment::create([
        'registration_id' => $registration->id,
        'amount' => 20000,
        'status' => 'complet',
        'remaining_balance' => 0,
        'month' => 'Juin',
        'payment_date' => '2026-06-10',
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    \Illuminate\Support\Facades\DB::table('payments')->where('id', $novemberPayment->id)->update(['created_at' => '2025-11-15 10:00:00']);
    \Illuminate\Support\Facades\DB::table('payments')->where('id', $junePayment->id)->update(['created_at' => '2026-06-10 10:00:00']);

    $response = $this->actingAs($manager)->get(route('accounting.dashboard'));
    $response->assertOk();
    $monthlyRevenue = collect($response->viewData('monthlyRevenue'));

    // 10 mois : octobre à décembre 2025, puis janvier à juillet 2026.
    expect($monthlyRevenue)->toHaveCount(10);
    expect($monthlyRevenue->first())->toMatchArray(['year' => 2025]);
    expect($monthlyRevenue->last())->toMatchArray(['year' => 2026]);

    $novemberLabel = \Illuminate\Support\Carbon::create(2025, 11, 1)->translatedFormat('F');
    $juneLabel = \Illuminate\Support\Carbon::create(2026, 6, 1)->translatedFormat('F');

    $novemberEntry = $monthlyRevenue->firstWhere(fn ($entry) => $entry['month'] === $novemberLabel && $entry['year'] === 2025);
    $juneEntry = $monthlyRevenue->firstWhere(fn ($entry) => $entry['month'] === $juneLabel && $entry['year'] === 2026);

    expect((float) $novemberEntry['amount'])->toBe(15000.0);
    expect((float) $juneEntry['amount'])->toBe(20000.0);
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
