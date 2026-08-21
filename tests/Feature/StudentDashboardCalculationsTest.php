<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Payment;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Support\Facades\Hash;

test("the remaining balance on the student's own dashboard matches FeeService, not monthly_fee times a fixed month count", function () {
    $year = SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    $registration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 25000,
        'monthly_fee' => 15000,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-DASH-001',
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
        'validated_by' => $student->id,
    ]);
    // Un paiement rejeté ne doit jamais être compté comme payé.
    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 999999,
        'status' => 'rejected',
        'remaining_balance' => 0,
        'month' => 'Novembre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
    ]);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    // Le paiement rejeté (999999) ne doit surtout pas apparaître comme "payé".
    expect((float) $response->viewData('totalPaid'))->toBe(15000.0);

    $expectedSituation = app(FeeService::class)->getFinancialSituation($registration->fresh());
    expect((float) $response->viewData('remaining'))->toBe((float) $expectedSituation['remaining']);
});

test('a student not yet re-enrolled in the new active school year can still log in and see their previous year on the dashboard', function () {
    // Cas "année blanche" : une nouvelle année scolaire est créée/activée, l'élève n'y a
    // pas encore d'inscription (pas encore réinscrit, ou en pause d'un an) — il doit
    // quand même pouvoir se connecter et retrouver sa dernière inscription connue
    // (classe, notes, paiements de l'année précédente), pas un compte bloqué ou une page
    // vide. Le jour où il revient, la réinscription réutilise le même compte/matricule
    // (RegistrationController::reenrollSearch) plutôt que d'en recréer un.
    $oldYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => false, 'status' => 'closed']);
    $oldClassroom = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'school_year_id' => $oldYear->id]);
    $student = User::factory()->create(['role' => 'eleve', 'password' => Hash::make('password')]);
    $student->assignRole('eleve');
    $oldRegistration = Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $oldClassroom->id,
        'registration_fee_paid' => 25000,
        'monthly_fee' => 15000,
        'registration_date' => now()->subYear()->toDateString(),
        'academic_year' => $oldYear->year_string,
        'school_year_id' => $oldYear->id,
        'matricule' => 'EDU-DASH-003',
        'status' => 'active',
    ]);

    // Nouvelle année active où l'élève n'a encore aucune inscription.
    SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true]);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    expect($response->viewData('registration')->id)->toBe($oldRegistration->id);
    $response->assertSee('CM1 A');

    // La connexion elle-même (pas seulement le dashboard une fois connecté) doit aussi
    // fonctionner : rien ne doit bloquer un compte élève juste parce qu'il n'a pas
    // d'inscription dans l'année scolaire active.
    $this->post('/logout');
    $loginResponse = $this->post('/login', [
        'matricule' => $student->matricule,
        'password' => 'password',
    ]);
    $this->assertAuthenticatedAs($student);
    $loginResponse->assertRedirect(route('student.dashboard'));
});

test("the general average on the student's own dashboard is weighted by subject coefficient, not a flat mean of every note", function () {
    $year = SchoolYear::create(['year_string' => '2026-2027', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $year->id]);
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    Registration::create([
        'user_id' => $student->id,
        'classroom_id' => $classroom->id,
        'registration_fee_paid' => 0,
        'monthly_fee' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => $year->year_string,
        'school_year_id' => $year->id,
        'matricule' => 'EDU-DASH-002',
        'status' => 'active',
    ]);

    $math = Matiere::factory()->create(['nom' => 'Mathématiques', 'coefficient' => 2]);
    $french = Matiere::factory()->create(['nom' => 'Français', 'coefficient' => 3]);
    foreach ([$math, $french] as $matiere) {
        PedagogicalAssignment::create([
            'teacher_id' => Teacher::factory()->create()->id,
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'school_year_id' => $year->id,
            'volume_horaire_hebdo' => 2,
            'is_active' => true,
        ]);
    }

    Note::create(['user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $math->id, 'valeur' => 10, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);
    Note::create(['user_id' => $student->id, 'classroom_id' => $classroom->id, 'matiere_id' => $french->id, 'valeur' => 15, 'type_evaluation' => 'composition', 'periode' => 'trimestre_1']);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    // (10*2 + 15*3) / (2+3) = 13, pas (10+15)/2 = 12.5 (moyenne arithmétique non pondérée).
    expect((float) $response->viewData('moyenne'))->toBe(13.0);
});
