<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ClassroomFee;
use App\Models\FeeType;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReenrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected SchoolYear $previousYear;

    protected SchoolYear $activeYear;

    protected Classroom $previousClassroom;

    protected Classroom $newClassroom;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);

        $this->previousYear = SchoolYear::factory()->create([
            'status' => 'completed',
            'is_active' => false,
            'year_string' => '2024-2025',
        ]);
        $this->activeYear = SchoolYear::factory()->create([
            'status' => 'active',
            'is_active' => true,
            'year_string' => '2025-2026',
        ]);
        $this->previousClassroom = Classroom::factory()->create(['school_year_id' => $this->previousYear->id]);
        $this->newClassroom = Classroom::factory()->create(['school_year_id' => $this->activeYear->id]);

        $this->student = User::factory()->create(['role' => 'eleve']);
        $this->student->syncRoles(['eleve']);

        Registration::create([
            'user_id' => $this->student->id,
            'classroom_id' => $this->previousClassroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
            'registration_date' => now()->subYear()->toDateString(),
            'academic_year' => $this->previousYear->year_string,
            'school_year_id' => $this->previousYear->id,
            'matricule' => 'EDU-25-000001',
            'status' => 'graduated',
        ]);
    }

    /** @test */
    public function it_can_show_the_reenrollment_search_form(): void
    {
        $response = $this->get(route('registrations.reinscription'));

        $response->assertOk()->assertViewIs('registrations.reinscription');
    }

    /** @test */
    public function searching_by_matricule_finds_the_existing_student_and_last_registration(): void
    {
        $response = $this->get(route('registrations.reinscription', ['matricule' => $this->student->matricule]));

        $response->assertOk();
        $response->assertViewHas('student', fn ($student) => $student->id === $this->student->id);
        $response->assertViewHas('lastRegistration', fn ($reg) => $reg->classroom_id === $this->previousClassroom->id);
    }

    /** @test */
    public function it_reenrolls_an_existing_student_without_creating_a_new_user(): void
    {
        $userCountBefore = User::count();

        $response = $this->post(route('registrations.reinscription.store'), [
            'user_id' => $this->student->id,
            'classroom_id' => $this->newClassroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 16000,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertSame($userCountBefore, User::count());

        $this->assertDatabaseHas('registrations', [
            'user_id' => $this->student->id,
            'classroom_id' => $this->newClassroom->id,
            'school_year_id' => $this->activeYear->id,
        ]);

        $this->student->refresh();
        $this->assertTrue($this->student->is_active);
    }

    /** @test */
    public function it_rejects_reenrollment_if_already_registered_for_the_active_year(): void
    {
        Registration::create([
            'user_id' => $this->student->id,
            'classroom_id' => $this->newClassroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 16000,
            'registration_date' => now()->toDateString(),
            'academic_year' => $this->activeYear->year_string,
            'school_year_id' => $this->activeYear->id,
            'matricule' => 'EDU-26-000002',
            'status' => 'active',
        ]);

        $response = $this->post(route('registrations.reinscription.store'), [
            'user_id' => $this->student->id,
            'classroom_id' => $this->newClassroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 16000,
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseCount('registrations', 2);
    }

    /** @test */
    public function fee_library_overrides_manual_input_for_non_privileged_users_on_reenrollment(): void
    {
        $inscriptionType = FeeType::firstOrCreate(['code' => 'inscription'], ['name' => "Frais d'inscription", 'is_recurring' => false, 'is_optional' => false]);
        $mensualiteType = FeeType::firstOrCreate(['code' => 'mensualite'], ['name' => 'Scolarité mensuelle', 'is_recurring' => true, 'is_optional' => false]);

        ClassroomFee::create([
            'classroom_id' => $this->newClassroom->id,
            'fee_type_id' => $inscriptionType->id,
            'school_year_id' => $this->activeYear->id,
            'amount' => 6000,
            'version' => 1,
            'is_current' => true,
        ]);
        ClassroomFee::create([
            'classroom_id' => $this->newClassroom->id,
            'fee_type_id' => $mensualiteType->id,
            'school_year_id' => $this->activeYear->id,
            'amount' => 18000,
            'version' => 1,
            'is_current' => true,
        ]);

        $this->post(route('registrations.reinscription.store'), [
            'user_id' => $this->student->id,
            'classroom_id' => $this->newClassroom->id,
            'registration_fee_paid' => 1,
            'monthly_fee' => 1,
        ]);

        $registration = Registration::where('user_id', $this->student->id)
            ->where('school_year_id', $this->activeYear->id)
            ->firstOrFail();

        $this->assertEquals(6000, $registration->registration_fee_paid);
        $this->assertEquals(18000, $registration->monthly_fee);
    }
}
