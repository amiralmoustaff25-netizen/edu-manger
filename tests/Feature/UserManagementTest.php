<?php

use App\Models\PedagogicalAssignment;
use App\Models\Teacher;
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
            'nom' => 'Comptable',
            'prenom' => 'Nouveau',
            'email' => 'nouveau.comptable@edumanager.sn',
            'role' => 'comptable',
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

test('admin cannot switch a non teacher account to the professeur role via user management', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $user = User::factory()->create(['role' => 'comptable']);
    $user->assignRole('comptable');

    $this->actingAs($admin)
        ->from(route('users.edit', $user))
        ->patch(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'professeur',
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.edit', $user))
        ->assertSessionHasErrors('role');

    expect($user->refresh()->role)->toBe('comptable');
    expect(Teacher::where('user_id', $user->id)->exists())->toBeFalse();
});

test('admin can edit an existing professeur account without changing its role', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $teacher = Teacher::factory()->create();
    $teacher->user->assignRole('professeur');

    $this->actingAs($admin)
        ->patch(route('users.update', $teacher->user), [
            'name' => 'Nom Modifié',
            'email' => $teacher->user->email,
            'role' => 'professeur',
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.index'));

    expect($teacher->user->refresh()->name)->toBe('Nom Modifié');
    expect($teacher->user->role)->toBe('professeur');
});

test('admin cannot change the role of a professeur with active pedagogical assignments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $teacher = Teacher::factory()->create();
    $teacher->user->assignRole('professeur');

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => \App\Models\Classroom::factory()->create()->id,
        'matiere_id' => \App\Models\Matiere::factory()->create()->id,
        'school_year_id' => \App\Models\SchoolYear::factory()->create(['is_active' => true])->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->from(route('users.edit', $teacher->user))
        ->patch(route('users.update', $teacher->user), [
            'name' => $teacher->user->name,
            'email' => $teacher->user->email,
            'role' => 'comptable',
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.edit', $teacher->user))
        ->assertSessionHasErrors('role');

    expect($teacher->user->refresh()->role)->toBe('professeur');
});
