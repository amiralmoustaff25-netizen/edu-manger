<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\StudentClassHistory;
use App\Models\User;
use App\Services\StudentPromotionService;
use App\Support\StudentStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StudentPromotionService $service;

    protected SchoolYear $sourceYear;

    protected SchoolYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->service = app(StudentPromotionService::class);
        $this->sourceYear = SchoolYear::factory()->create(['is_active' => false]);
        $this->activeYear = SchoolYear::factory()->create(['is_active' => true]);
    }

    private function activeRegistration(Classroom $classroom): Registration
    {
        $student = User::factory()->create(['role' => 'eleve']);

        return Registration::create([
            'user_id' => $student->id, 'classroom_id' => $classroom->id, 'school_year_id' => $this->sourceYear->id,
            'registration_fee_paid' => 0, 'monthly_fee' => 0, 'registration_date' => now()->toDateString(),
            'academic_year' => $this->sourceYear->year_string, 'matricule' => 'ELE-'.uniqid(), 'status' => StudentStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function preview_suggests_promotion_when_a_next_level_classroom_exists(): void
    {
        $cm1 = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $this->sourceYear->id]);
        $cm2 = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->activeYear->id]);
        $registration = $this->activeRegistration($cm1);

        $preview = $this->service->preview($this->sourceYear);

        $row = collect($preview)->firstWhere('registration_id', $registration->id);
        $this->assertEquals('promote', $row['suggested_action']);
        $this->assertEquals($cm2->id, $row['suggested_classroom_id']);
    }

    /** @test */
    public function preview_suggests_repeat_when_no_next_level_but_same_level_exists(): void
    {
        $terminale = Classroom::create(['name' => 'Terminale A', 'cycle' => 'lycee', 'ordre' => 13, 'school_year_id' => $this->sourceYear->id]);
        $terminaleNext = Classroom::create(['name' => 'Terminale A', 'cycle' => 'lycee', 'ordre' => 13, 'school_year_id' => $this->activeYear->id]);
        $registration = $this->activeRegistration($terminale);

        $preview = $this->service->preview($this->sourceYear);

        $row = collect($preview)->firstWhere('registration_id', $registration->id);
        $this->assertEquals('repeat', $row['suggested_action']);
        $this->assertEquals($terminaleNext->id, $row['suggested_classroom_id']);
    }

    /** @test */
    public function preview_prefers_the_explicit_promotes_to_classroom_mapping_over_ordre(): void
    {
        // Deux sections parallèles au même niveau (ordre identique) : la déduction par
        // ordre seul ne pourrait pas savoir laquelle des deux classes CM2 correspond à
        // "CM1 A" — le mapping explicite promotes_to_classroom_id lève l'ambiguïté.
        $cm2A = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->activeYear->id]);
        $cm2B = Classroom::create(['name' => 'CM2 B', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->activeYear->id]);
        $cm1A = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $this->sourceYear->id, 'promotes_to_classroom_id' => $cm2B->id]);
        $registration = $this->activeRegistration($cm1A);

        $preview = $this->service->preview($this->sourceYear);

        $row = collect($preview)->firstWhere('registration_id', $registration->id);
        $this->assertEquals('promote', $row['suggested_action']);
        $this->assertEquals($cm2B->id, $row['suggested_classroom_id']);
        $this->assertNotEquals($cm2A->id, $row['suggested_classroom_id']);
    }

    /** @test */
    public function preview_falls_back_to_ordre_when_the_explicit_mapping_targets_a_classroom_outside_the_active_year(): void
    {
        // Mapping configuré mais devenu obsolète (classe cible pas dans l'année active,
        // ex. l'admin ne l'a jamais mis à jour) : ne doit pas faire échouer la suggestion,
        // juste retomber sur la déduction par ordre comme si rien n'était configuré.
        $staleTarget = Classroom::create(['name' => 'CM2 Ancienne', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->sourceYear->id]);
        $cm2 = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->activeYear->id]);
        $cm1 = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $this->sourceYear->id, 'promotes_to_classroom_id' => $staleTarget->id]);
        $registration = $this->activeRegistration($cm1);

        $preview = $this->service->preview($this->sourceYear);

        $row = collect($preview)->firstWhere('registration_id', $registration->id);
        $this->assertEquals('promote', $row['suggested_action']);
        $this->assertEquals($cm2->id, $row['suggested_classroom_id']);
    }

    /** @test */
    public function preview_suggests_manual_when_classroom_has_no_ordre(): void
    {
        $classroom = Classroom::create(['name' => 'Classe spéciale', 'cycle' => 'primaire', 'ordre' => null, 'school_year_id' => $this->sourceYear->id]);
        $registration = $this->activeRegistration($classroom);

        $preview = $this->service->preview($this->sourceYear);

        $row = collect($preview)->firstWhere('registration_id', $registration->id);
        $this->assertEquals('manual', $row['suggested_action']);
        $this->assertNull($row['suggested_classroom_id']);
    }

    /** @test */
    public function apply_promote_creates_a_new_registration_in_the_active_year_and_records_history(): void
    {
        $cm1 = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $this->sourceYear->id]);
        $cm2 = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->activeYear->id]);
        $registration = $this->activeRegistration($cm1);

        $results = $this->service->apply([
            $registration->id => ['action' => 'promote', 'classroom_id' => $cm2->id],
        ]);

        $this->assertEquals(1, $results['promoted']);
        $this->assertEquals([], $results['errors']);
        $this->assertDatabaseHas('registrations', [
            'user_id' => $registration->user_id, 'classroom_id' => $cm2->id, 'school_year_id' => $this->activeYear->id,
        ]);
        $this->assertDatabaseHas('student_class_history', [
            'user_id' => $registration->user_id, 'classroom_id' => $cm1->id, 'school_year_id' => $this->sourceYear->id, 'resultat' => 'admis',
        ]);
    }

    /** @test */
    public function apply_repeat_enrolls_the_student_back_in_a_same_level_classroom(): void
    {
        $classroom = Classroom::create(['name' => 'CE1 A', 'cycle' => 'primaire', 'ordre' => 3, 'school_year_id' => $this->sourceYear->id]);
        $sameLevelTarget = Classroom::create(['name' => 'CE1 A', 'cycle' => 'primaire', 'ordre' => 3, 'school_year_id' => $this->activeYear->id]);
        $registration = $this->activeRegistration($classroom);

        $results = $this->service->apply([
            $registration->id => ['action' => 'repeat', 'classroom_id' => $sameLevelTarget->id],
        ]);

        $this->assertEquals(1, $results['repeated']);
        $this->assertDatabaseHas('student_class_history', ['user_id' => $registration->user_id, 'resultat' => 'redouble']);
    }

    /** @test */
    public function apply_graduate_transitions_the_registration_status_without_creating_a_new_one(): void
    {
        $classroom = Classroom::create(['name' => 'Terminale A', 'cycle' => 'lycee', 'ordre' => 13, 'school_year_id' => $this->sourceYear->id]);
        $registration = $this->activeRegistration($classroom);

        $results = $this->service->apply([
            $registration->id => ['action' => 'graduate'],
        ]);

        $this->assertEquals(1, $results['graduated']);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => StudentStatus::GRADUATED]);
        $this->assertEquals(1, Registration::where('user_id', $registration->user_id)->count());
        $this->assertDatabaseHas('student_class_history', ['user_id' => $registration->user_id, 'resultat' => StudentStatus::GRADUATED]);
    }

    /** @test */
    public function apply_expel_without_a_reason_is_recorded_as_an_error_since_a_reason_is_required(): void
    {
        $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->sourceYear->id]);
        $registration = $this->activeRegistration($classroom);

        $results = $this->service->apply([
            $registration->id => ['action' => 'expel'],
        ]);

        $this->assertEquals(0, $results['expelled']);
        $this->assertCount(1, $results['errors']);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => StudentStatus::ACTIVE]);
    }

    /** @test */
    public function apply_expel_with_a_reason_succeeds(): void
    {
        $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->sourceYear->id]);
        $registration = $this->activeRegistration($classroom);

        $results = $this->service->apply([
            $registration->id => ['action' => 'expel', 'reason' => 'Comportement disciplinaire grave'],
        ]);

        $this->assertEquals(1, $results['expelled']);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => StudentStatus::EXPELLED]);
    }

    /** @test */
    public function apply_rejects_a_target_classroom_that_does_not_belong_to_the_active_year(): void
    {
        $classroom = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $this->sourceYear->id]);
        $wrongYearClassroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $this->sourceYear->id]);
        $registration = $this->activeRegistration($classroom);

        $results = $this->service->apply([
            $registration->id => ['action' => 'promote', 'classroom_id' => $wrongYearClassroom->id],
        ]);

        $this->assertEquals(0, $results['promoted']);
        $this->assertCount(1, $results['errors']);
    }

    /** @test */
    public function apply_skip_does_nothing(): void
    {
        $classroom = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $this->sourceYear->id]);
        $registration = $this->activeRegistration($classroom);

        $results = $this->service->apply([
            $registration->id => ['action' => 'skip'],
        ]);

        $this->assertEquals(1, $results['skipped']);
        $this->assertEquals(1, Registration::where('user_id', $registration->user_id)->count());
        $this->assertEquals(0, StudentClassHistory::count());
    }
}
