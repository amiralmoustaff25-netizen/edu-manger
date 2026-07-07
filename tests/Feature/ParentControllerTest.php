<?php

namespace Tests\Feature;

use App\Models\ParentModel;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentControllerTest extends TestCase
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
    public function it_can_list_all_parents(): void
    {
        ParentModel::factory()->count(3)->create();

        $response = $this->get(route('parents.index'));

        $response->assertOk();
    }

    /** @test */
    public function it_can_show_a_single_parent(): void
    {
        $parent = ParentModel::factory()->create();

        $response = $this->get(route('parents.show', $parent));

        $response->assertOk();
    }

    /** @test */
    public function it_can_create_a_new_parent(): void
    {
        $response = $this->post(route('parents.store'), [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
            'telephone' => '0612345678',
            'adresse' => '12 Rue de Paris',
            'profession' => 'Ingénieur',
            'statut' => 'actif',
        ]);

        $response->assertRedirect(route('parents.index'));
        $this->assertDatabaseHas('parents', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
        ]);
    }

    /** @test */
    public function it_can_update_an_existing_parent(): void
    {
        $parent = ParentModel::factory()->create();

        $response = $this->put(route('parents.update', $parent), [
            'nom' => $parent->nom,
            'prenom' => $parent->prenom,
            'email' => $parent->email,
            'statut' => $parent->statut,
            'telephone' => '0698765432',
            'adresse' => 'Nouvelle adresse',
            'profession' => 'Médecin',
        ]);

        $response->assertRedirect(route('parents.show', $parent));
        $this->assertDatabaseHas('parents', [
            'id' => $parent->id,
            'telephone' => '0698765432',
            'adresse' => 'Nouvelle adresse',
            'profession' => 'Médecin',
        ]);
    }

    /** @test */
    public function it_can_delete_a_parent(): void
    {
        $parent = ParentModel::factory()->create();

        $response = $this->delete(route('parents.destroy', $parent));

        $response->assertRedirect(route('parents.index'));
        $this->assertDatabaseMissing('parents', [
            'id' => $parent->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_creation(): void
    {
        $response = $this->post(route('parents.store'), []);

        $response->assertSessionHasErrors(['nom', 'prenom', 'email', 'statut']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_parent(): void
    {
        $response = $this->get(route('parents.show', 9999));

        $response->assertNotFound();
    }
}
