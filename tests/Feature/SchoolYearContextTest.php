<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\SchoolYearContext;
use App\Support\StudentStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolYearContextTest extends TestCase
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
    public function current_defaults_to_the_active_year_when_nothing_is_selected(): void
    {
        $active = SchoolYear::factory()->create(['is_active' => true]);

        $context = app(SchoolYearContext::class);

        $this->assertTrue($context->current()->is($active));
        $this->assertTrue($context->isViewingActiveYear());
    }

    /** @test */
    public function set_persists_the_selection_across_requests_via_session(): void
    {
        $active = SchoolYear::factory()->create(['is_active' => true]);
        $past = SchoolYear::factory()->create(['is_active' => false]);

        $this->actingAs($this->admin)->post(route('context.school-year.update'), ['school_year_id' => $past->id]);

        $context = app(SchoolYearContext::class);
        $this->assertTrue($context->current()->is($past));
        $this->assertFalse($context->isViewingActiveYear());
    }

    /** @test */
    public function students_index_only_shows_students_registered_in_the_viewed_year(): void
    {
        $yearA = SchoolYear::factory()->create(['is_active' => true, 'year_string' => '2031-2032']);
        $yearB = SchoolYear::factory()->create(['is_active' => false, 'year_string' => '2032-2033']);
        $classroomA = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $yearA->id]);
        $classroomB = Classroom::create(['name' => 'CM2 A', 'cycle' => 'primaire', 'school_year_id' => $yearB->id]);

        $studentA = User::factory()->create(['role' => 'eleve', 'name' => 'Élève Année A']);
        Registration::create([
            'user_id' => $studentA->id, 'classroom_id' => $classroomA->id, 'school_year_id' => $yearA->id,
            'registration_fee_paid' => 0, 'monthly_fee' => 0, 'registration_date' => now()->toDateString(),
            'academic_year' => $yearA->year_string, 'matricule' => 'ELE-A', 'status' => StudentStatus::ACTIVE,
        ]);

        $studentB = User::factory()->create(['role' => 'eleve', 'name' => 'Élève Année B']);
        Registration::create([
            'user_id' => $studentB->id, 'classroom_id' => $classroomB->id, 'school_year_id' => $yearB->id,
            'registration_fee_paid' => 0, 'monthly_fee' => 0, 'registration_date' => now()->toDateString(),
            'academic_year' => $yearB->year_string, 'matricule' => 'ELE-B', 'status' => StudentStatus::ACTIVE,
        ]);

        // Par défaut (année active = A) : seul l'élève A apparaît.
        $this->actingAs($this->admin)->get(route('students.index'))
            ->assertOk()->assertSee('Élève Année A')->assertDontSee('Élève Année B');

        // Après avoir changé le contexte vers B : seul l'élève B apparaît.
        $this->actingAs($this->admin)->post(route('context.school-year.update'), ['school_year_id' => $yearB->id]);
        $this->actingAs($this->admin)->get(route('students.index'))
            ->assertOk()->assertSee('Élève Année B')->assertDontSee('Élève Année A');
    }

    /** @test */
    public function classrooms_index_only_shows_classrooms_of_the_viewed_year(): void
    {
        $yearA = SchoolYear::factory()->create(['is_active' => true]);
        $yearB = SchoolYear::factory()->create(['is_active' => false]);
        Classroom::create(['name' => 'Classe Année A', 'cycle' => 'primaire', 'school_year_id' => $yearA->id]);
        Classroom::create(['name' => 'Classe Année B', 'cycle' => 'primaire', 'school_year_id' => $yearB->id]);

        $this->actingAs($this->admin)->get(route('classrooms.index'))
            ->assertOk()->assertSee('Classe Année A')->assertDontSee('Classe Année B');

        $this->actingAs($this->admin)->post(route('context.school-year.update'), ['school_year_id' => $yearB->id]);
        $this->actingAs($this->admin)->get(route('classrooms.index'))
            ->assertOk()->assertSee('Classe Année B')->assertDontSee('Classe Année A');
    }

    /** @test */
    public function bulletins_index_only_shows_classrooms_of_the_viewed_year(): void
    {
        $yearA = SchoolYear::factory()->create(['is_active' => true]);
        $yearB = SchoolYear::factory()->create(['is_active' => false]);
        Classroom::create(['name' => 'Classe Bulletin A', 'cycle' => 'primaire', 'school_year_id' => $yearA->id]);
        Classroom::create(['name' => 'Classe Bulletin B', 'cycle' => 'primaire', 'school_year_id' => $yearB->id]);

        $this->admin->givePermissionTo('generer-bulletins');

        $this->actingAs($this->admin)->get(route('bulletins.index'))
            ->assertOk()->assertSee('Classe Bulletin A')->assertDontSee('Classe Bulletin B');

        $this->actingAs($this->admin)->post(route('context.school-year.update'), ['school_year_id' => $yearB->id]);
        $this->actingAs($this->admin)->get(route('bulletins.index'))
            ->assertOk()->assertSee('Classe Bulletin B')->assertDontSee('Classe Bulletin A');
    }

    /** @test */
    public function the_selector_is_not_shown_to_students_or_parents(): void
    {
        SchoolYear::factory()->create(['is_active' => true]);
        $student = User::factory()->create(['role' => 'eleve']);
        $student->assignRole('eleve');

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('Année consultée');
    }
}
