<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin can view the user listing', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Gestion des utilisateurs');
});

test('comptable cannot access user management', function () {
    $comptable = User::factory()->create(['role' => 'comptable']);
    $comptable->assignRole('comptable');

    $this->actingAs($comptable)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('admin can create a user with a generated matricule and temporary password', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Nouveau Comptable',
            'email' => 'nouveau.comptable@edumanager.sn',
            'role' => 'comptable',
            'contract_started_at' => '2026-06-17',
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.index'));

    $user = User::where('email', 'nouveau.comptable@edumanager.sn')->firstOrFail();

    expect($user->matricule)->toStartWith('CPT-');
    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($user->created_by)->toBe($admin->id);
    expect($user->hasRole('comptable'))->toBeTrue();
});

test('admin can deactivate and reactivate a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $user = User::factory()->create(['role' => 'professeur', 'is_active' => true]);
    $user->assignRole('professeur');

    $this->actingAs($admin)
        ->patch(route('users.toggle', $user))
        ->assertRedirect();

    expect($user->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('users.toggle', $user))
        ->assertRedirect();

    expect($user->refresh()->is_active)->toBeTrue();
});

test('admin archives users with soft delete', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $user = User::factory()->create(['role' => 'professeur', 'is_active' => true]);
    $user->assignRole('professeur');

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect(User::withTrashed()->find($user->id))->not->toBeNull();
    expect(User::withTrashed()->find($user->id)->trashed())->toBeTrue();
});

test('admin can reset a user password', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $user = User::factory()->create([
        'role' => 'professeur',
        'password' => Hash::make('old-password'),
        'password_must_change' => false,
    ]);
    $user->assignRole('professeur');

    $this->actingAs($admin)
        ->patch(route('users.reset-password', $user))
        ->assertRedirect();

    $user->refresh();

    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($user->password_must_change)->toBeTrue();
});
