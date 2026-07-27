<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->superAdmin = User::factory()->create(['is_active' => true]);
    $this->superAdmin->assignRole('super-admin');
});

test('super_admin_can_search_user_by_matricule_and_view_roles', function () {
    $teacher = User::factory()->create(['matricule' => 'PROF-260015', 'is_active' => true]);
    $teacher->assignRole('professeur');

    $response = actingAs($this->superAdmin)
        ->get(route('users.roles.index', ['search' => 'PROF-260015']));

    $response->assertOk();
    $response->assertSee('PROF-260015');
    $response->assertSee('Professeur');
});

test('permissions_inherited_from_roles_are_shown_checked', function () {
    $user = User::factory()->create(['matricule' => 'MGR-260001', 'is_active' => true]);
    $user->assignRole('manager-comptable');

    $response = actingAs($this->superAdmin)
        ->get(route('users.roles.index', ['search' => 'MGR-260001']));

    $response->assertOk();
    $response->assertSee('checked', false);
});

test('super_admin_can_assign_role_and_sync_permissions', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('professeur');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['professeur', 'manager-comptable'],
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->hasRole(['professeur', 'manager-comptable']))->toBeTrue();
    expect($user->can('voir-paiements'))->toBeTrue();
    expect($user->role)->toBe('professeur');
});

test('removing_role_also_removes_its_permissions_when_not_granted_elsewhere', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('comptable');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), ['roles' => []])
        ->assertRedirect();

    $user->refresh();
    expect($user->roles)->toBeEmpty();
    expect($user->can('enregistrer-paiement'))->toBeFalse();
});

test('cannot_remove_last_active_super_admin_role', function () {
    $response = actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $this->superAdmin), ['roles' => ['professeur']]);

    $response->assertStatus(422);
    $this->superAdmin->refresh();
    expect($this->superAdmin->hasRole('super-admin'))->toBeTrue();
});

test('super_admin_role_requires_confirmation', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('professeur');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['professeur', 'super-admin'],
        ])
        ->assertSessionHasErrors('confirm_super_admin');

    $user->refresh();
    expect($user->hasRole('super-admin'))->toBeFalse();
});

test('role_changes_are_traced_in_history', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('professeur');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['professeur', 'comptable'],
        ]);

    $this->assertDatabaseHas('user_role_history', [
        'user_id' => $user->id,
        'changed_by' => $this->superAdmin->id,
        'action' => 'assigned',
        'role' => 'comptable',
    ]);
});

test('direct_permissions_can_be_assigned_exceptionally', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('comptable');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['comptable'],
            'direct_permissions' => ['modifier-paiement'],
        ]);

    $user->refresh();
    expect($user->hasDirectPermission('modifier-paiement'))->toBeTrue();
});

test('non_admin_cannot_access_role_assignment', function () {
    $teacher = User::factory()->create(['is_active' => true]);
    $teacher->assignRole('professeur');

    actingAs($teacher)
        ->get(route('users.roles.index'))
        ->assertForbidden();
});
