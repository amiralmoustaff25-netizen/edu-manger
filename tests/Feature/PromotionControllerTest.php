<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Support\StudentStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function a_user_without_the_permission_cannot_access_the_promotion_assistant(): void
    {
        $user = User::factory()->create();
        $user->assignRole('professeur');

        $this->actingAs($user)->get(route('promotion.index'))->assertForbidden();
    }

    /** @test */
    public function an_admin_can_see_the_preview_for_a_source_year(): void
    {
        $sourceYear = SchoolYear::factory()->create(['is_active' => false]);
        $activeYear = SchoolYear::factory()->create(['is_active' => true]);
        $classroom = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $sourceYear->id]);
        $student = User::factory()->create(['role' => 'eleve']);
        Registration::create([
            'user_id' => $student->id, 'classroom_id' => $classroom->id, 'school_year_id' => $sourceYear->id,
            'registration_fee_paid' => 0, 'monthly_fee' => 0, 'registration_date' => now()->toDateString(),
            'academic_year' => $sourceYear->year_string, 'matricule' => 'ELE-TEST-1', 'status' => StudentStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->get(route('promotion.index', ['source_year_id' => $sourceYear->id]));

        $response->assertOk()->assertSee($student->name)->assertSee('CM1 A');
    }

    /** @test */
    public function an_admin_can_execute_a_promotion_and_it_is_audit_logged(): void
    {
        $sourceYear = SchoolYear::factory()->create(['is_active' => false]);
        $activeYear = SchoolYear::factory()->create(['is_active' => true]);
        $cm1 = Classroom::create(['name' => 'CM1 A', 'cycle' => 'primaire', 'ordre' => 5, 'school_year_id' => $sourceYear->id]);
        $cm2 = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'ordre' => 6, 'school_year_id' => $activeYear->id]);
        $student = User::factory()->create(['role' => 'eleve']);
        $registration = Registration::create([
            'user_id' => $student->id, 'classroom_id' => $cm1->id, 'school_year_id' => $sourceYear->id,
            'registration_fee_paid' => 0, 'monthly_fee' => 0, 'registration_date' => now()->toDateString(),
            'academic_year' => $sourceYear->year_string, 'matricule' => 'ELE-TEST-2', 'status' => StudentStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('promotion.store'), [
            'source_year_id' => $sourceYear->id,
            'decisions' => [
                $registration->id => ['action' => 'promote', 'classroom_id' => $cm2->id],
            ],
        ]);

        $response->assertRedirect(route('promotion.index'));
        $this->assertDatabaseHas('registrations', ['user_id' => $student->id, 'classroom_id' => $cm2->id, 'school_year_id' => $activeYear->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'students_promoted']);
    }
}
