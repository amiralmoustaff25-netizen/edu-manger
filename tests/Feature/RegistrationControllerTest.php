<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected SchoolYear $schoolYear;

    protected Classroom $classroom;

    protected ParentModel $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);

        // Année scolaire active obligatoire
        $this->schoolYear = SchoolYear::factory()->create([
            'status' => 'active',
            'is_active' => true,
            'year_string' => '2025-2026',
        ]);
        $this->classroom = Classroom::factory()->create([
            'school_year_id' => $this->schoolYear->id,
        ]);
        $this->parent = ParentModel::factory()->create();
    }

    /** @test */
    public function it_can_show_registration_create_form(): void
    {
        $response = $this->get(route('registrations.create'));

        $response->assertOk()
            ->assertViewIs('registrations.create');
    }

    /** @test */
    public function it_can_store_a_new_registration(): void
    {
        $student = User::factory()->create(['role' => 'eleve']);

        $response = $this->post(route('registrations.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('registrations', [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'school_year_id' => $this->schoolYear->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_store(): void
    {
        $response = $this->post(route('registrations.store'), []);

        $response->assertSessionHasErrors(['user_id', 'classroom_id', 'registration_fee_paid', 'monthly_fee']);
    }

    /** @test */
    public function it_generates_matricule_on_registration(): void
    {
        $student = User::factory()->create(['role' => 'eleve']);

        $this->post(route('registrations.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ]);

        $registration = Registration::where('user_id', $student->id)->first();
        $this->assertNotNull($registration);
        $this->assertNotNull($registration->matricule);
        $this->assertStringStartsWith('EDU-', $registration->matricule);
    }

    /** @test */
    public function it_prevents_duplicate_registration_same_year(): void
    {
        $student = User::factory()->create(['role' => 'eleve']);

        // Première inscription
        $this->post(route('registrations.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ]);

        // Deuxième inscription même année → doit échouer
        $response = $this->post(route('registrations.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    /** @test */
    public function it_requires_active_school_year(): void
    {
        // Désactiver l'année active
        $this->schoolYear->update(['is_active' => false]);

        $student = User::factory()->create(['role' => 'eleve']);

        $response = $this->post(route('registrations.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ]);

        // store() uses firstOrFail() which throws 404 when no active year exists
        $response->assertNotFound();
    }

    /** @test */
    public function it_can_complete_full_registration_cycle(): void
    {
        // Créer un élève avec le champ name (pas nom/prenom)
        $student = User::factory()->create([
            'role' => 'eleve',
            'name' => 'Jean Dupont',
        ]);
        $student->assignRole('eleve');

        // Inscrire l'élève
        $response = $this->post(route('registrations.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('registrations', [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'school_year_id' => $this->schoolYear->id,
        ]);
    }

    /** @test */
    public function it_calculates_registration_with_payments(): void
    {
        $student = User::factory()->create(['role' => 'eleve']);

        $response = $this->post(route('registrations.store'), [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 10000,
            'monthly_fee' => 20000,
        ]);

        $response->assertRedirect(route('dashboard'));

        $registration = Registration::where('user_id', $student->id)->first();
        $this->assertNotNull($registration);
        $this->assertEquals(20000, $registration->monthly_fee);
        $this->assertEquals(10000, $registration->registration_fee_paid);
        $this->assertEquals('pending', $registration->status);
    }
}
