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

        // assertOk() seul ne suffit pas : un <x-slot> sans <x-app-layout> englobant ne
        // provoque pas forcément un code d'erreur HTTP, seulement une page rendue sans
        // layout (pas de <title>, pas de sidebar). On vérifie donc que le layout complet
        // est bien présent, pas seulement que la requête n'a pas échoué.
        $response->assertOk()
            ->assertSee('<title>', false)
            ->assertSee('Fiche Parent');
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
    public function an_archived_parent_appears_when_filtering_by_archived_status(): void
    {
        // Régression Phase 3 (finding H7) : archive() soft-supprime le parent, mais
        // index() n'utilisait jamais withTrashed() — le filtre "Archivés" ne pouvait
        // structurellement jamais rien retourner.
        $parent = ParentModel::factory()->create(['statut' => 'actif']);
        $this->patch(route('parents.archive', $parent));

        $response = $this->get(route('parents.index', ['statut' => 'archive']));

        $response->assertOk()->assertSee($parent->nom);
    }

    /** @test */
    public function an_archived_parent_can_still_be_viewed_instead_of_404(): void
    {
        // Régression Phase 3 (finding H7) : ParentModel n'avait pas de resolveRouteBinding()
        // personnalisé, donc {parent} dans les routes renvoyait 404 pour tout parent
        // archivé — devenu visible dans la liste par le fix ci-dessus, mais menant nulle
        // part au clic sur "Voir" sans ce second correctif.
        $parent = ParentModel::factory()->create();
        $this->patch(route('parents.archive', $parent));

        $response = $this->get(route('parents.show', $parent));

        $response->assertOk();
    }

    /** @test */
    public function an_admin_can_restore_an_archived_parent(): void
    {
        $parent = ParentModel::factory()->create(['statut' => 'actif']);
        $this->patch(route('parents.archive', $parent));
        $this->assertTrue($parent->fresh()->trashed());

        $response = $this->post(route('parents.restore', $parent->id));

        $response->assertRedirect();
        $parent->refresh();
        $this->assertFalse($parent->trashed());
        $this->assertSame('actif', $parent->statut);
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

    /** @test */
    public function parent_policy_ability_methods_are_actually_reached_by_kebab_case_permission_names(): void
    {
        // Régression Phase 2 (finding M5) : ParentController::authorize() passe des noms de
        // permission kebab-case ('voir-detail-parent', 'modifier-parent', ...), pas les noms
        // conventionnels ('view', 'update', ...). Avant le correctif, Laravel ne trouvait
        // aucune méthode correspondante sur ParentPolicy et retombait systématiquement sur le
        // Gate plat de Spatie, qui ignore l'instance $parent ciblée — la logique "un parent
        // peut voir/modifier son propre profil" de la policy était donc du code mort. On le
        // vérifie ici directement via Gate, sans passer par les routes (bloquées par le
        // middleware role:super-admin|admin, hors sujet pour ce test).
        $parentUser = User::factory()->create();
        $parentUser->assignRole('parent');
        $parentRecord = ParentModel::factory()->create(['user_id' => $parentUser->id]);

        $otherParentUser = User::factory()->create();
        $otherParentUser->assignRole('parent');

        $this->assertTrue($parentUser->can('voir-detail-parent', $parentRecord));
        $this->assertTrue($parentUser->can('modifier-parent', $parentRecord));
        $this->assertFalse($otherParentUser->can('voir-detail-parent', $parentRecord));
    }
}
