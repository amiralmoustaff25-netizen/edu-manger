<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_all_classrooms(): void
    {
        Classroom::factory()->count(3)->create();

        $response = $this->get(route('classrooms.index'));

        $response->assertOk()
            ->assertViewIs('classrooms.index')
            ->assertViewHas('classrooms');
    }

    /** @test */
    public function it_can_show_create_form(): void
    {
        $response = $this->get(route('classrooms.create'));

        $response->assertOk()
            ->assertViewIs('classrooms.create');
    }

    /** @test */
    public function it_can_store_a_new_classroom(): void
    {
        $response = $this->post(route('classrooms.store'), [
            'nom' => 'Classe A',
            'niveau' => 'CP',
            'capacite' => 25,
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', [
            'nom' => 'Classe A',
            'niveau' => 'CP',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_store(): void
    {
        $response = $this->post(route('classrooms.store'), []);

        $response->assertSessionHasErrors(['nom', 'niveau']);
    }

    /** @test */
    public function it_can_show_edit_form(): void
    {
        $classroom = Classroom::factory()->create();

        $response = $this->get(route('classrooms.edit', $classroom));

        $response->assertOk()
            ->assertViewIs('classrooms.edit')
            ->assertViewHas('classroom');
    }

    /** @test */
    public function it_can_update_a_classroom(): void
    {
        $classroom = Classroom::factory()->create();

        $response = $this->put(route('classrooms.update', $classroom), [
            'nom' => 'Classe B',
            'niveau' => 'CE1',
            'capacite' => 30,
        ]);

        $response->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'nom' => 'Classe B',
        ]);
    }

    /** @test */
    public function it_can_delete_a_classroom(): void
    {
        $classroom = Classroom::factory()->create();

        $response = $this->delete(route('classrooms.destroy', $classroom));

        $response->assertRedirect(route('classrooms.index'));
        $this->assertDatabaseMissing('classrooms', [
            'id' => $classroom->id,
        ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_classroom(): void
    {
        $response = $this->get(route('classrooms.edit', 9999));

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_filter_classrooms_by_niveau(): void
    {
        Classroom::factory()->count(2)->create(['niveau' => 'CP']);
        Classroom::factory()->count(1)->create(['niveau' => 'CE1']);

        $response = $this->get(route('classrooms.index', ['niveau' => 'CP']));

        $response->assertOk()
            ->assertViewHas('classrooms', function ($classrooms) {
                return $classrooms->count() === 2;
            });
    }
}