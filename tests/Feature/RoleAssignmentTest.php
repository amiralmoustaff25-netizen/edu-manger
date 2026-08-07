<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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
    $response->assertSee('value="voir-paiements"', false);
    $response->assertSee('checked', false);
});

test('super_admin_can_assign_role_and_sync_permissions', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('professeur');

    $desiredRoles = ['professeur', 'manager-comptable'];
    $expectedPermissions = Permission::whereHas('roles', fn ($query) => $query->whereIn('name', $desiredRoles))
        ->pluck('name')
        ->unique()
        ->values()
        ->toArray();

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => $desiredRoles,
            'permissions' => $expectedPermissions,
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->hasRole($desiredRoles))->toBeTrue();
    expect($user->can('voir-paiements'))->toBeTrue();
    expect($user->role)->toBe('professeur');
});

test('removing_role_also_removes_its_permissions_when_not_granted_elsewhere', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('comptable');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => [],
            'permissions' => [],
        ])
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
            'permissions' => ['modifier-paiement'],
        ]);

    $user->refresh();
    expect($user->can('modifier-paiement'))->toBeTrue();
});

test('non_admin_cannot_access_role_assignment', function () {
    $teacher = User::factory()->create(['is_active' => true]);
    $teacher->assignRole('professeur');

    actingAs($teacher)
        ->get(route('users.roles.index'))
        ->assertForbidden();
});

test('inherited_permission_can_be_revoked_for_a_user', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('manager-comptable');

    expect($user->can('valider-paiement-partiel'))->toBeTrue();

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['manager-comptable'],
            'permissions' => array_values(array_diff(
                $user->getAllPermissions()->pluck('name')->toArray(),
                ['valider-paiement-partiel']
            )),
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->can('valider-paiement-partiel'))->toBeFalse();
});

test('revoked_permission_can_be_granted_back_directly', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('comptable');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['comptable'],
            'permissions' => array_merge(
                $user->getAllPermissions()->pluck('name')->toArray(),
                ['valider-paiement-partiel']
            ),
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->can('valider-paiement-partiel'))->toBeTrue();
});

test('an admin cannot assign the super-admin role to another user', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole('professeur');

    actingAs($admin)
        ->patch(route('users.roles.update', $target), [
            'roles' => ['professeur', 'super-admin'],
            'confirm_super_admin' => '1',
        ])
        ->assertForbidden();

    expect($target->refresh()->hasRole('super-admin'))->toBeFalse();
});

test('an admin cannot modify the roles or permissions of an existing super-admin account', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');

    actingAs($admin)
        ->patch(route('users.roles.update', $this->superAdmin), [
            'roles' => ['admin'],
        ])
        ->assertForbidden();

    expect($this->superAdmin->refresh()->hasRole('super-admin'))->toBeTrue();
});

test('an admin cannot set a user role to super-admin via the user edit form', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');
    $target = User::factory()->create(['is_active' => true, 'role' => 'comptable']);
    $target->assignRole('comptable');

    actingAs($admin)
        ->from(route('users.edit', $target))
        ->patch(route('users.update', $target), [
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => $target->email,
            'role' => 'super-admin',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('role');

    expect($target->refresh()->role)->toBe('comptable');
});

test('an admin cannot edit an existing super-admin account via the user edit form', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');

    actingAs($admin)->get(route('users.edit', $this->superAdmin))->assertForbidden();

    actingAs($admin)
        ->patch(route('users.update', $this->superAdmin), [
            'nom' => 'Hack',
            'prenom' => 'Attempt',
            'email' => $this->superAdmin->email,
            'role' => 'super-admin',
            'is_active' => '0',
        ])
        ->assertForbidden();

    expect($this->superAdmin->refresh()->is_active)->toBeTrue();
});

test('a super-admin can assign the super-admin role with confirmation', function () {
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole('professeur');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $target), [
            'roles' => ['professeur', 'super-admin'],
            'confirm_super_admin' => '1',
        ])
        ->assertRedirect();

    expect($target->refresh()->hasRole('super-admin'))->toBeTrue();
});

test('payment_validation_route_respects_permission_revocation', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('manager-comptable');

    actingAs($this->superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['manager-comptable'],
            'permissions' => array_values(array_diff(
                $user->getAllPermissions()->pluck('name')->toArray(),
                ['valider-paiement-partiel']
            )),
        ])
        ->assertRedirect();

    $user->refresh();
    actingAs($user)
        ->get(route('payments.validation'))
        ->assertForbidden();
});
