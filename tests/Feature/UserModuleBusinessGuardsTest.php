<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->admin->assignRole('admin');
});

test('editing a user with a multi word surname does not truncate it (regression H1)', function () {
    $user = User::factory()->create(['name' => 'El Hadji Diop Amadou', 'prenom' => 'Amadou', 'role' => 'comptable']);
    $user->assignRole('comptable');

    $editPage = $this->actingAs($this->admin)->get(route('users.edit', $user));

    $editPage->assertOk()->assertSee('value="El Hadji Diop"', false);

    $this->actingAs($this->admin)
        ->patch(route('users.update', $user), [
            'nom' => 'El Hadji Diop',
            'prenom' => 'Amadou',
            'email' => $user->email,
            'role' => 'comptable',
            'is_active' => '1',
        ])
        ->assertSessionDoesntHaveErrors();

    expect($user->refresh()->name)->toBe('El Hadji Diop Amadou');
});

test('admin cannot assign the eleve role to an existing account via the generic user form (H2)', function () {
    $user = User::factory()->create(['role' => 'comptable']);
    $user->assignRole('comptable');

    $this->actingAs($this->admin)
        ->from(route('users.edit', $user))
        ->patch(route('users.update', $user), [
            'nom' => $user->name,
            'prenom' => 'X',
            'email' => $user->email,
            'role' => 'eleve',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('role');

    expect($user->refresh()->role)->toBe('comptable');
});

test('admin cannot change the role of a student with an active registration (H2)', function () {
    $student = User::factory()->create(['role' => 'eleve']);
    $student->assignRole('eleve');
    Registration::factory()->create(['user_id' => $student->id, 'status' => 'active']);

    $this->actingAs($this->admin)
        ->from(route('users.edit', $student))
        ->patch(route('users.update', $student), [
            'nom' => $student->name,
            'prenom' => 'X',
            'email' => $student->email,
            'role' => 'comptable',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('role');

    expect($student->refresh()->role)->toBe('eleve');
});

test('the role assignment page blocks removing professeur from a teacher with active pedagogical assignments (H3)', function () {
    $superAdmin = User::factory()->create(['is_active' => true]);
    $superAdmin->assignRole('super-admin');

    $teacher = Teacher::factory()->create();
    $teacher->user->assignRole('professeur');
    $teacher->user->update(['role' => 'professeur']);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => Classroom::factory()->create()->id,
        'matiere_id' => Matiere::factory()->create()->id,
        'school_year_id' => SchoolYear::factory()->create(['is_active' => true])->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($superAdmin)
        ->patch(route('users.roles.update', $teacher->user), [
            'roles' => ['comptable'],
        ])
        ->assertStatus(422);

    expect($teacher->user->refresh()->hasRole('professeur'))->toBeTrue();
});

test('the role assignment page refuses to grant the eleve role without a dedicated student profile (H3)', function () {
    $superAdmin = User::factory()->create(['is_active' => true]);
    $superAdmin->assignRole('super-admin');

    $user = User::factory()->create(['role' => 'comptable']);
    $user->assignRole('comptable');

    $this->actingAs($superAdmin)
        ->patch(route('users.roles.update', $user), [
            'roles' => ['eleve'],
        ])
        ->assertStatus(422);

    expect($user->refresh()->hasRole('eleve'))->toBeFalse();
});

test('an archived user can be restored from the listing and regains access (H4)', function () {
    $user = User::factory()->create(['role' => 'comptable', 'is_active' => true]);
    $user->assignRole('comptable');

    $this->actingAs($this->admin)->delete(route('users.destroy', $user))->assertRedirect();
    expect($user->fresh()->trashed())->toBeTrue();

    $this->actingAs($this->admin)
        ->post(route('users.restore', $user->id))
        ->assertRedirect(route('users.index'));

    $restored = User::find($user->id);
    expect($restored->trashed())->toBeFalse();
    expect($restored->is_active)->toBeTrue();
});

test('the archived users filter lists soft deleted accounts with a restore action', function () {
    $user = User::factory()->create(['role' => 'comptable']);
    $user->assignRole('comptable');
    $user->delete();

    $this->actingAs($this->admin)
        ->get(route('users.index', ['status' => 'archived']))
        ->assertOk()
        ->assertSee($user->matricule)
        ->assertSee('Restaurer');
});

test('the email of an archived account can be reused when creating a new user (M1)', function () {
    $archived = User::factory()->create(['email' => 'reuse@edumanager.sn', 'role' => 'comptable']);
    $archived->assignRole('comptable');
    $archived->delete();

    $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'nom' => 'Nouveau',
            'prenom' => 'Titulaire',
            'email' => 'reuse@edumanager.sn',
            'role' => 'surveillant',
            'is_active' => '1',
        ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'reuse@edumanager.sn')->whereNull('deleted_at')->exists())->toBeTrue();
});

test('archiving a professeur with active pedagogical assignments is blocked (M5)', function () {
    $teacher = Teacher::factory()->create();
    $teacher->user->assignRole('professeur');
    $teacher->user->update(['role' => 'professeur']);

    PedagogicalAssignment::create([
        'teacher_id' => $teacher->id,
        'classroom_id' => Classroom::factory()->create()->id,
        'matiere_id' => Matiere::factory()->create()->id,
        'school_year_id' => SchoolYear::factory()->create(['is_active' => true])->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('users.destroy', $teacher->user))
        ->assertSessionHasErrors('user');

    expect($teacher->user->refresh()->trashed())->toBeFalse();
});

test('the professeur eleve and parent roles are only offered on the edit form for accounts that already hold them (F1)', function () {
    $comptable = User::factory()->create(['role' => 'comptable']);
    $comptable->assignRole('comptable');

    $teacher = Teacher::factory()->create();
    $teacher->user->assignRole('professeur');
    $teacher->user->update(['role' => 'professeur']);

    $comptableEdit = $this->actingAs($this->admin)->get(route('users.edit', $comptable));
    $comptableEdit->assertOk()->assertDontSee('>professeur<', false)->assertDontSee('>eleve<', false)->assertDontSee('>parent<', false);

    $teacherEdit = $this->actingAs($this->admin)->get(route('users.edit', $teacher->user));
    $teacherEdit->assertOk()->assertSee('>professeur<', false);
});
