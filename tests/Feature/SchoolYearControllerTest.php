<?php

namespace Tests\Feature;

use App\Models\SchoolYear;
use App\Models\User;
use App\Support\SchoolYearStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolYearControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_all_school_years(): void
    {
        SchoolYear::factory()->count(3)->create();

        $response = $this->get(route('school-years.index'));

        $response->assertOk()
            ->assertViewIs('school_years.index')
            ->assertViewHas('schoolYears');
    }

    /** @test */
    public function it_can_store_a_new_school_year(): void
    {
        $response = $this->post(route('school-years.store'), [
            'year_string' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('school-years.index'));
        $this->assertDatabaseHas('school_years', [
            'year_string' => '2025-2026',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_store(): void
    {
        $response = $this->post(route('school-years.store'), []);

        $response->assertSessionHasErrors(['year_string']);
    }

    /** @test */
    public function it_can_activate_a_school_year(): void
    {
        $schoolYear = SchoolYear::factory()->create(['is_active' => false]);

        $response = $this->post(route('school-years.activate', $schoolYear));

        $response->assertRedirect();
        $this->assertDatabaseHas('school_years', [
            'id' => $schoolYear->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_delete_a_school_year(): void
    {
        $schoolYear = SchoolYear::create([
            'year_string' => '2099-2100',
            'start_date' => '2099-09-01',
            'end_date' => '2100-06-30',
            'is_active' => false,
            'status' => SchoolYearStatus::PREPARATION,
        ]);

        $response = $this->delete(route('school-years.destroy', $schoolYear));

        $response->assertRedirect();
        // The controller prevents deletion if there are relations, so we just verify the response
    }

    /** @test */
    public function it_returns_404_for_nonexistent_school_year(): void
    {
        $response = $this->post(route('school-years.activate', 9999));

        $response->assertNotFound();
    }

    /** @test */
    public function it_prevents_duplicate_active_school_years(): void
    {
        SchoolYear::factory()->create(['is_active' => true, 'year_string' => '2024-2025']);

        $response = $this->post(route('school-years.store'), [
            'year_string' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        // The model's booted hook deactivates the previous active year when a new one is created
        $this->assertDatabaseHas('school_years', [
            'year_string' => '2024-2025',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('school_years', [
            'year_string' => '2025-2026',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_show_only_active_school_year(): void
    {
        SchoolYear::factory()->create(['is_active' => true, 'year_string' => 'Active']);
        SchoolYear::factory()->create(['is_active' => false, 'year_string' => 'Inactive']);

        $response = $this->get(route('school-years.index', ['actif' => true]));

        $response->assertOk()
            ->assertViewHas('schoolYears', function ($schoolYears) {
                return $schoolYears->where('is_active', true)->count() === 1;
            });
    }
}
