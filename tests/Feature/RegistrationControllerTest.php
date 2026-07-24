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
        $response = $this->post(route('registrations.store'), $this->registrationPayload());

        $response->assertRedirect(route('dashboard'));
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $this->assertTrue($student->hasRole('eleve'));
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

        $response->assertSessionHasErrors(['nom', 'prenom', 'email', 'date_naissance', 'lieu_naissance', 'sexe', 'cycle', 'classroom_id', 'registration_fee_paid', 'monthly_fee']);
    }

    /** @test */
    public function it_generates_matricule_on_registration(): void
    {
        $this->post(route('registrations.store'), $this->registrationPayload());

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $registration = Registration::where('user_id', $student->id)->first();
        $this->assertNotNull($registration);
        $this->assertNotNull($registration->matricule);
        $this->assertStringStartsWith('EDU-', $registration->matricule);
        $this->assertStringStartsWith('ELE-', $student->matricule);
    }

    /** @test */
    public function it_prevents_duplicate_registration_same_year(): void
    {
        $this->post(route('registrations.store'), $this->registrationPayload());

        $response = $this->post(route('registrations.store'), $this->registrationPayload());

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('registrations', 1);
    }

    /** @test */
    public function it_requires_active_school_year(): void
    {
        // Désactiver l'année active
        $this->schoolYear->update(['is_active' => false]);

        $response = $this->post(route('registrations.store'), $this->registrationPayload());

        // store() uses firstOrFail() which throws 404 when no active year exists
        $response->assertNotFound();
    }

    /** @test */
    public function it_can_complete_full_registration_cycle(): void
    {
        $response = $this->post(route('registrations.store'), $this->registrationPayload([
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
        ]));

        $response->assertRedirect(route('dashboard'));

        $student = User::where('email', 'jean.dupont@example.com')->firstOrFail();
        $this->assertSame('Dupont Jean', $student->name);
        $this->assertDatabaseHas('registrations', [
            'user_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'school_year_id' => $this->schoolYear->id,
        ]);
    }

    /** @test */
    public function it_calculates_registration_with_payments(): void
    {
        $response = $this->post(route('registrations.store'), $this->registrationPayload([
            'registration_fee_paid' => 10000,
            'monthly_fee' => 20000,
        ]));

        $response->assertRedirect(route('dashboard'));

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $registration = Registration::where('user_id', $student->id)->first();
        $this->assertNotNull($registration);
        $this->assertEquals(20000, $registration->monthly_fee);
        $this->assertEquals(10000, $registration->registration_fee_paid);
        $this->assertEquals('pending', $registration->status);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'nom' => 'Diallo',
            'prenom' => 'Aminata',
            'email' => 'student@example.com',
            'date_naissance' => '2010-05-12',
            'lieu_naissance' => 'Dakar',
            'sexe' => 'F',
            'cycle' => 'primaire',
            'role' => 'eleve',
            'is_active' => 1,
            'classroom_id' => $this->classroom->id,
            'registration_fee_paid' => 5000,
            'monthly_fee' => 15000,
        ], $overrides);
    }
}
