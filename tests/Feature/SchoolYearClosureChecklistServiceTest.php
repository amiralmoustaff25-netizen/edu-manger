<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\FeeType;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Payment;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\StudentDocument;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SchoolYearClosureChecklistService;
use App\Support\SchoolYearStatus;
use App\Support\StudentStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolYearClosureChecklistServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SchoolYearClosureChecklistService $service;

    protected SchoolYear $schoolYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->service = app(SchoolYearClosureChecklistService::class);
        $this->schoolYear = SchoolYear::factory()->create(['is_active' => true, 'status' => SchoolYearStatus::ACTIVE]);
    }

    private function activeRegistration(Classroom $classroom, array $overrides = []): Registration
    {
        return Registration::factory()->create(array_merge([
            'classroom_id' => $classroom->id,
            'school_year_id' => $this->schoolYear->id,
            'status' => StudentStatus::ACTIVE,
            'registration_fee_paid' => 25000,
            'monthly_fee' => 15000,
        ], $overrides));
    }

    /** @test */
    public function a_year_with_nothing_in_it_reports_no_blocking_anomaly(): void
    {
        $this->assertFalse($this->service->hasBlockingAnomalies($this->schoolYear));
        $this->assertEquals(0, $this->service->anomalyCount($this->schoolYear));
    }

    /** @test */
    public function the_three_not_applicable_checks_are_always_reported_as_such(): void
    {
        $checklist = $this->service->check($this->schoolYear);

        $this->assertEquals('not_applicable', collect($checklist['comptabilite'])->firstWhere('key', 'remises_non_validees')['status']);
        $this->assertEquals('not_applicable', collect($checklist['pedagogie'])->firstWhere('key', 'bulletins_non_generes')['status']);
        $this->assertEquals('not_applicable', collect($checklist['pedagogie'])->firstWhere('key', 'conseils_de_classe_non_termines')['status']);
    }

    /** @test */
    public function it_flags_a_registration_with_an_outstanding_balance(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $registration = $this->activeRegistration($classroom);

        $feeType = FeeType::create(['name' => 'Mensualité', 'code' => 'mensualite']);
        \App\Models\ClassroomFee::create([
            'classroom_id' => $classroom->id,
            'fee_type_id' => $feeType->id,
            'school_year_id' => $this->schoolYear->id,
            'amount' => 15000,
            'version' => 1,
            'is_current' => true,
        ]);

        // Aucun paiement enregistré : solde restant dû > 0.
        $result = collect($this->service->check($this->schoolYear)['comptabilite'])->firstWhere('key', 'paiements_non_termines');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertGreaterThan(0, $result['count']);
        $this->assertContains($registration->user->name, $result['items']);
    }

    /** @test */
    public function it_flags_a_pending_partial_payment(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $registration = $this->activeRegistration($classroom);

        Payment::create([
            'registration_id' => $registration->id,
            'amount' => 5000,
            'month' => 'Octobre',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'espèces',
            'status' => 'partiel',
        ]);

        $result = collect($this->service->check($this->schoolYear)['comptabilite'])->firstWhere('key', 'paiements_partiels_non_valides');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function it_flags_a_registration_with_unconfigured_fees(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $this->activeRegistration($classroom, ['monthly_fee' => 0]);

        $result = collect($this->service->check($this->schoolYear)['comptabilite'])->firstWhere('key', 'anomalies_comptables');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function it_flags_a_pedagogical_assignment_without_any_grade(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $matiere = Matiere::factory()->create();
        $teacher = Teacher::factory()->create();

        PedagogicalAssignment::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'school_year_id' => $this->schoolYear->id,
            'is_active' => true,
        ]);

        $result = collect($this->service->check($this->schoolYear)['pedagogie'])->firstWhere('key', 'notes_non_saisies');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function a_pedagogical_assignment_with_at_least_one_grade_is_not_flagged(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $matiere = Matiere::factory()->create();
        $teacher = Teacher::factory()->create();

        PedagogicalAssignment::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'school_year_id' => $this->schoolYear->id,
            'is_active' => true,
        ]);

        Note::factory()->create(['classroom_id' => $classroom->id, 'matiere_id' => $matiere->id]);

        $result = collect($this->service->check($this->schoolYear)['pedagogie'])->firstWhere('key', 'notes_non_saisies');

        $this->assertEquals('ok', $result['status']);
    }

    /** @test */
    public function it_flags_an_unvalidated_grade(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        Note::factory()->create(['classroom_id' => $classroom->id, 'validated_at' => null]);

        $result = collect($this->service->check($this->schoolYear)['pedagogie'])->firstWhere('key', 'notes_non_validees');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function a_validated_grade_is_not_flagged(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $validator = User::factory()->create();
        Note::factory()->create(['classroom_id' => $classroom->id, 'validated_at' => now(), 'validated_by' => $validator->id]);

        $result = collect($this->service->check($this->schoolYear)['pedagogie'])->firstWhere('key', 'notes_non_validees');

        $this->assertEquals('ok', $result['status']);
    }

    /** @test */
    public function it_flags_a_student_whose_classroom_was_archived(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $registration = $this->activeRegistration($classroom);
        $classroom->delete(); // soft delete

        $result = collect($this->service->check($this->schoolYear)['administration'])->firstWhere('key', 'eleves_sans_classe');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function it_flags_a_teacher_without_any_assignment(): void
    {
        Teacher::factory()->create();

        $result = collect($this->service->check($this->schoolYear)['administration'])->firstWhere('key', 'enseignants_sans_affectation');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function a_teacher_with_an_active_assignment_is_not_flagged(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $matiere = Matiere::factory()->create();
        $teacher = Teacher::factory()->create();

        PedagogicalAssignment::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'matiere_id' => $matiere->id,
            'school_year_id' => $this->schoolYear->id,
            'is_active' => true,
        ]);

        $result = collect($this->service->check($this->schoolYear)['administration'])->firstWhere('key', 'enseignants_sans_affectation');

        $this->assertEquals('ok', $result['status']);
    }

    /** @test */
    public function it_flags_a_student_without_any_document(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $this->activeRegistration($classroom);

        $result = collect($this->service->check($this->schoolYear)['administration'])->firstWhere('key', 'documents_manquants');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function a_student_with_a_document_is_not_flagged(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $registration = $this->activeRegistration($classroom);

        StudentDocument::create([
            'user_id' => $registration->user_id,
            'type' => 'piece_identite',
            'original_filename' => 'piece.pdf',
            'path' => 'documents/piece.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $result = collect($this->service->check($this->schoolYear)['administration'])->firstWhere('key', 'documents_manquants');

        $this->assertEquals('ok', $result['status']);
    }

    /** @test */
    public function it_flags_a_registration_without_a_matricule(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $this->activeRegistration($classroom, ['matricule' => null]);

        $result = collect($this->service->check($this->schoolYear)['administration'])->firstWhere('key', 'inscriptions_incompletes');

        $this->assertEquals('anomaly', $result['status']);
        $this->assertEquals(1, $result['count']);
    }

    /** @test */
    public function has_blocking_anomalies_and_anomaly_count_reflect_real_anomalies(): void
    {
        $classroom = Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
        $this->activeRegistration($classroom, ['matricule' => null]);

        $this->assertTrue($this->service->hasBlockingAnomalies($this->schoolYear));
        $this->assertGreaterThan(0, $this->service->anomalyCount($this->schoolYear));
    }
}
