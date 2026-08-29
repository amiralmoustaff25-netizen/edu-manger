<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\ClassroomFee;
use App\Models\FeeType;
use App\Models\GradeSetting;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\SubjectConfiguration;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SchoolYearConfigDuplicationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolYearConfigDuplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SchoolYearConfigDuplicationService $service;

    protected SchoolYear $source;

    protected SchoolYear $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->service = app(SchoolYearConfigDuplicationService::class);
        $this->source = SchoolYear::factory()->create(['year_string' => '2025-2026']);
        $this->target = SchoolYear::factory()->create(['year_string' => '2026-2027']);
    }

    /** @test */
    public function it_duplicates_classrooms_as_empty_shells(): void
    {
        $teacher = User::factory()->create(['role' => 'professeur']);
        $classroom = Classroom::create([
            'name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $this->source->id,
            'teacher_id' => $teacher->id, 'max_students' => 30,
        ]);

        $summary = $this->service->duplicate($this->source, $this->target);

        $this->assertEquals(1, $summary['classrooms']);
        $newClassroom = Classroom::where('school_year_id', $this->target->id)->first();
        $this->assertNotNull($newClassroom);
        $this->assertEquals('CM2 A', $newClassroom->name);
        $this->assertEquals('primaire', $newClassroom->cycle);
        $this->assertEquals($teacher->id, $newClassroom->teacher_id);
        $this->assertEquals(30, $newClassroom->max_students);
        $this->assertNotEquals($classroom->id, $newClassroom->id);
        $this->assertEquals(0, $newClassroom->registrations()->count());
    }

    /** @test */
    public function it_duplicates_periods_with_dates_shifted_by_one_year_and_resets_execution_state(): void
    {
        AcademicPeriod::create([
            'school_year_id' => $this->source->id, 'name' => 'Trimestre 1', 'code' => 'trimestre_1',
            'position' => 1, 'starts_at' => '2025-09-01', 'ends_at' => '2025-12-20',
            'status' => 'closed', 'grade_entry_open' => true,
        ]);

        $summary = $this->service->duplicate($this->source, $this->target);

        $this->assertEquals(1, $summary['periods']);
        $newPeriod = AcademicPeriod::where('school_year_id', $this->target->id)->first();
        $this->assertNotNull($newPeriod);
        $this->assertEquals('2026-09-01', $newPeriod->starts_at->format('Y-m-d'));
        $this->assertEquals('2026-12-20', $newPeriod->ends_at->format('Y-m-d'));
        $this->assertEquals('draft', $newPeriod->status);
        $this->assertFalse($newPeriod->grade_entry_open);
    }

    /** @test */
    public function it_duplicates_grade_settings(): void
    {
        GradeSetting::create([
            'school_year_id' => $this->source->id, 'organization_mode' => 'semesters',
            'default_scale' => 20, 'minimum_grade' => 0, 'allow_decimals' => true, 'decimal_places' => 2,
            'allow_appreciations' => true, 'allow_edit_after_submission' => false,
            'administrative_validation_required' => true, 'lock_after_validation' => true,
        ]);

        $summary = $this->service->duplicate($this->source, $this->target);

        $this->assertTrue($summary['grade_settings']);
        $newSettings = GradeSetting::where('school_year_id', $this->target->id)->first();
        $this->assertNotNull($newSettings);
        $this->assertEquals('semesters', $newSettings->organization_mode);
        $this->assertTrue($newSettings->administrative_validation_required);
    }

    /** @test */
    public function it_does_not_fail_when_there_are_no_grade_settings_to_duplicate(): void
    {
        $summary = $this->service->duplicate($this->source, $this->target);

        $this->assertFalse($summary['grade_settings']);
        $this->assertEquals(0, GradeSetting::where('school_year_id', $this->target->id)->count());
    }

    /** @test */
    public function it_duplicates_subject_configurations_and_remaps_classroom_scoped_ones(): void
    {
        $matiere = Matiere::factory()->create();
        $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $this->source->id]);

        SubjectConfiguration::create([
            'matiere_id' => $matiere->id, 'school_year_id' => $this->source->id,
            'cycle' => 'primaire', 'classroom_id' => $classroom->id, 'coefficient' => 2, 'is_active' => true,
        ]);
        SubjectConfiguration::create([
            'matiere_id' => $matiere->id, 'school_year_id' => $this->source->id,
            'cycle' => 'college', 'classroom_id' => null, 'coefficient' => 3, 'is_active' => true,
        ]);

        $summary = $this->service->duplicate($this->source, $this->target);

        $this->assertEquals(2, $summary['subject_configurations']);
        $newClassroom = Classroom::where('school_year_id', $this->target->id)->first();

        $this->assertDatabaseHas('subject_configurations', [
            'school_year_id' => $this->target->id, 'classroom_id' => $newClassroom->id, 'coefficient' => 2,
        ]);
        $this->assertDatabaseHas('subject_configurations', [
            'school_year_id' => $this->target->id, 'classroom_id' => null, 'coefficient' => 3,
        ]);
    }

    /** @test */
    public function it_duplicates_only_active_pedagogical_assignments_and_resets_deactivation_state(): void
    {
        $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $this->source->id]);
        $teacher = Teacher::factory()->create();
        $matiere = Matiere::factory()->create();

        PedagogicalAssignment::create([
            'teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => $matiere->id,
            'school_year_id' => $this->source->id, 'volume_horaire_hebdo' => 6, 'is_active' => true,
        ]);
        PedagogicalAssignment::create([
            'teacher_id' => $teacher->id, 'classroom_id' => $classroom->id, 'matiere_id' => Matiere::factory()->create()->id,
            'school_year_id' => $this->source->id, 'volume_horaire_hebdo' => 4, 'is_active' => false,
            // deactivated_by référence users.id (pas teachers.id) — voir le même correctif
            // pour Attendance::recorded_by dans AttendanceController/TeachingSessionController.
            'deactivated_at' => now(), 'deactivated_by' => $teacher->user_id,
        ]);

        $summary = $this->service->duplicate($this->source, $this->target);

        $this->assertEquals(1, $summary['pedagogical_assignments']);
        $newAssignment = PedagogicalAssignment::where('school_year_id', $this->target->id)->first();
        $this->assertNotNull($newAssignment);
        $this->assertEquals(6, $newAssignment->volume_horaire_hebdo);
        $this->assertTrue($newAssignment->is_active);
        $this->assertNull($newAssignment->deactivated_at);
    }

    /** @test */
    public function it_duplicates_only_current_classroom_fees_reset_to_version_one(): void
    {
        $classroom = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $this->source->id]);
        $feeType = FeeType::create(['name' => 'Mensualité', 'code' => 'mensualite']);

        $old = ClassroomFee::create([
            'classroom_id' => $classroom->id, 'fee_type_id' => $feeType->id, 'school_year_id' => $this->source->id,
            'amount' => 12000, 'version' => 1, 'is_current' => false,
        ]);
        ClassroomFee::create([
            'classroom_id' => $classroom->id, 'fee_type_id' => $feeType->id, 'school_year_id' => $this->source->id,
            'amount' => 15000, 'version' => 2, 'is_current' => true, 'previous_id' => $old->id,
        ]);

        $summary = $this->service->duplicate($this->source, $this->target);

        $this->assertEquals(1, $summary['classroom_fees']);
        $newFee = ClassroomFee::where('school_year_id', $this->target->id)->first();
        $this->assertNotNull($newFee);
        $this->assertEquals('15000.00', $newFee->amount);
        $this->assertEquals(1, $newFee->version);
        $this->assertTrue($newFee->is_current);
        $this->assertNull($newFee->previous_id);
    }

    /** @test */
    public function creating_a_school_year_with_duplicate_from_id_triggers_duplication_end_to_end(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        AcademicPeriod::create([
            'school_year_id' => $this->source->id, 'name' => 'Trimestre 1', 'code' => 'trimestre_1',
            'position' => 1, 'starts_at' => '2025-09-01', 'ends_at' => '2025-12-20',
        ]);

        $response = $this->post(route('school-years.store'), [
            'year_string' => '2030-2031',
            'duplicate_from_id' => $this->source->id,
        ]);

        $response->assertRedirect(route('school-years.index'));
        $newYear = SchoolYear::where('year_string', '2030-2031')->first();
        $this->assertNotNull($newYear);
        $this->assertEquals(1, AcademicPeriod::where('school_year_id', $newYear->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'school_year_config_duplicated', 'model_id' => $newYear->id]);
    }

    /** @test */
    public function creating_a_school_year_without_duplicate_from_id_does_not_trigger_duplication(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        AcademicPeriod::create([
            'school_year_id' => $this->source->id, 'name' => 'Trimestre 1', 'code' => 'trimestre_1',
            'position' => 1, 'starts_at' => '2025-09-01', 'ends_at' => '2025-12-20',
        ]);

        $response = $this->post(route('school-years.store'), ['year_string' => '2031-2032']);

        $response->assertRedirect(route('school-years.index'));
        $newYear = SchoolYear::where('year_string', '2031-2032')->first();
        $this->assertEquals(0, AcademicPeriod::where('school_year_id', $newYear->id)->count());
    }
}
