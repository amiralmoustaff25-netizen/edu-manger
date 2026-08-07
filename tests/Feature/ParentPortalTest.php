<?php

use App\Models\ParentModel;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createParentPortalFixture(): array
{
    $parentUser = User::factory()->create(['role' => 'parent']);
    $parentUser->assignRole('parent');
    $parentProfile = ParentModel::factory()->create(['user_id' => $parentUser->id, 'statut' => 'actif']);

    $child = User::factory()->create(['role' => 'eleve']);
    $child->assignRole('eleve');
    $parentProfile->students()->attach($child->id, ['lien_parente' => 'Pere']);

    $otherChild = User::factory()->create(['role' => 'eleve']);
    $otherChild->assignRole('eleve');

    return [$parentUser, $parentProfile, $child, $otherChild];
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a parent can reach the portal dashboard (route ordering does not shadow it)', function () {
    [$parentUser, , $child] = createParentPortalFixture();

    $response = $this->actingAs($parentUser)->get(route('parents.dashboard'));

    $response->assertOk();
    $response->assertSee($child->name);
});

test('a parent can see the list of their own children', function () {
    [$parentUser, , $child] = createParentPortalFixture();

    $response = $this->actingAs($parentUser)->get(route('parents.children.index'));

    $response->assertOk();
    $response->assertSee($child->name);
});

test('a parent can view their own child profile', function () {
    [$parentUser, , $child] = createParentPortalFixture();

    $response = $this->actingAs($parentUser)
        ->get(route('parents.children.profile', ['student' => $child->id]));

    $response->assertOk();
});

test('a parent cannot view a child that is not theirs (IDOR protection)', function () {
    [$parentUser, , , $otherChild] = createParentPortalFixture();

    $response = $this->actingAs($parentUser)
        ->get(route('parents.children.profile', ['student' => $otherChild->id]));

    // resolveStudentFromRequest() ne retrouve rien pour un enfant qui n'est pas le
    // sien : redirection vers le dashboard plutôt que la fiche d'un tiers.
    $response->assertRedirect(route('parents.dashboard'));
});

test('the admin parents resource still works and is not shadowed by the parent portal routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    $parent = ParentModel::factory()->create();

    $this->actingAs($admin)
        ->get(route('parents.show', $parent))
        ->assertOk();
});
