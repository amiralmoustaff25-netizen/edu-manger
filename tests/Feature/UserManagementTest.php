<?php

use App\Models\Classroom;
use App\Models\Matiere;
use App\Models\PedagogicalAssignment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the edit form actually saves changes as submitted by the real page (regression: name vs nom/prenom mismatch)', function () {
    // Ce test soumet exactement les champs que resources/views/users/_form.blade.php
    // envoie réellement (nom/prenom), pas des champs choisis pour faire passer le test.
    // Avant correctif, UpdateUserRequest exigeait un champ "name" que le formulaire
    // n'envoie jamais : la modification d'un utilisateur échouait systématiquement
    // en 422 depuis l'interface, alors que les tests qui postaient "name" directement
    // passaient sans jamais exercer ce bug.
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    $user = User::factory()->create(['role' => 'comptable', 'name' => 'Ancien Nom']);
    $user->assignRole('comptable');

    $editPage = $this->actingAs($admin)->get(route('users.edit', $user));
    $editPage->assertOk()->assertSee('name="nom"', false)->assertSee('name="prenom"', false)->assertDontSee('name="name"', false);

    $this->actingAs($admin)
        ->patch(route('users.update', $user), [
            'nom' => 'Nouveau',
            'prenom' => 'Nom',
            'email' => $user->email,
            'role' => 'comptable',
            'is_active' => '1',
        ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('users.index'));

    expect($user->refresh()->name)->toBe('Nouveau Nom');
});

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
    // Le mot de passe temporaire est désormais généré aléatoirement (plus jamais le
    // littéral "password", prévisible et identique pour tout compte créé) : on ne
    // peut donc plus vérifier sa valeur exacte, seulement qu'un changement est exigé
    // et que le mot de passe stocké n'est pas le mot de passe faible historique.
    expect(Hash::check('password', $user->password))->toBeFalse();
    expect($user->password_must_change)->toBeTrue();
    expect($user->created_by)->toBe($admin->id);
    expect($user->hasRole('comptable'))->toBeTrue();
});

test('admin can create a surveillant account even though hidden professeur fiche fields are submitted empty', function () {
    // Régression : la section "Fiche professeur" du formulaire générique
    // (_form.blade.php) reste dans le DOM (masquée via x-show, pas x-if) quel que
    // soit le rôle choisi, donc un vrai navigateur soumet ces champs vides pour tout
    // rôle non-professeur — ex. date_naissance="", sexe="". Sans 'nullable' en plus
    // de Rule::requiredIf(false) dans StoreUserRequest, ces chaînes vides faisaient
    // échouer 'date'/Rule::in et bloquaient silencieusement la création de tout
    // compte non-professeur (surveillant, comptable...) depuis un vrai navigateur.
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'nom' => 'Ndiaye',
            'prenom' => 'Ousmane',
            'email' => '',
            'role' => 'surveillant',
            'is_active' => '1',
            'date_naissance' => '',
            'lieu_naissance' => '',
            'sexe' => '',
            'nationalite' => '',
            'diplomes' => '',
            'etablissements_formation' => '',
            'statut' => '',
            'date_recrutement' => '',
            'specialites' => '',
            'filiation' => '',
            'contact_urgence_nom' => '',
            'contact_urgence_tel' => '',
        ])
        ->assertRedirect(route('users.index'));

    $user = User::where('name', 'Ndiaye Ousmane')->firstOrFail();

    expect($user->matricule)->toStartWith('SURV-');
    expect($user->hasRole('surveillant'))->toBeTrue();
});

test('admin can create a professeur account with its fiche métier from the generic user form', function () {
    // Le module Professeurs séparé (bouton "Ajouter un professeur") a été retiré pour
    // simplifier la navigation : "Ajouter un utilisateur" est désormais l'unique point
    // d'entrée, y compris pour un professeur, dont la fiche métier (Teacher) doit donc
    // être collectée dans ce même formulaire quand role=professeur est sélectionné.
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'nom' => 'Sarr',
        'prenom' => 'Ousmane',
        'email' => 'ousmane.sarr@edumanager.sn',
        'role' => 'professeur',
        'is_active' => '1',
        'date_naissance' => '1985-05-15',
        'lieu_naissance' => 'Dakar',
        'sexe' => 'masculin',
        'nationalite' => 'Sénégalaise',
        'diplomes' => 'Licence en mathématiques',
        'etablissements_formation' => 'UCAD',
        'statut' => 'fonctionnaire',
        'date_recrutement' => '2020-09-01',
        'specialites' => 'Mathématiques, Physique',
        'filiation' => 'Fils de M. Sarr',
        'contact_urgence_nom' => 'Mme Sarr',
        'contact_urgence_tel' => '770000000',
        'nombre_heures_semaine' => 18,
    ]);

    $response->assertSessionDoesntHaveErrors()->assertRedirect(route('users.index'));

    $user = User::where('email', 'ousmane.sarr@edumanager.sn')->firstOrFail();
    expect($user->hasRole('professeur'))->toBeTrue();
    expect($user->matricule)->toStartWith('PROF');

    $teacher = Teacher::where('user_id', $user->id)->firstOrFail();
    expect($teacher->statut)->toBe('fonctionnaire');
    expect($teacher->specialites)->toBe(['Mathématiques', 'Physique']);
});

test('creating a professeur from the generic user form requires the fiche métier fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'nom' => 'Sarr',
            'prenom' => 'Ousmane',
            'email' => 'ousmane.sarr2@edumanager.sn',
            'role' => 'professeur',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors(['date_naissance', 'lieu_naissance', 'sexe', 'nationalite', 'diplomes', 'etablissements_formation', 'statut', 'date_recrutement', 'specialites', 'filiation', 'contact_urgence_nom', 'contact_urgence_tel']);

    expect(User::where('email', 'ousmane.sarr2@edumanager.sn')->exists())->toBeFalse();
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

    $oldPasswordHash = $user->password;
    $user->refresh();

    expect($user->password)->not->toBe($oldPasswordHash);
    expect(Hash::check('old-password', $user->password))->toBeFalse();
    expect($user->password_must_change)->toBeTrue();
});

test('resetting a user password sets it to the fixed default password (edu.default_reset_password)', function () {
    // Choix délibéré : un mot de passe fixe est plus facile à communiquer par
    // téléphone/papier à un parent ou professeur qu'un mot de passe aléatoire — le
    // compte reste forcé de le changer à la prochaine connexion (password_must_change).
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $user = User::factory()->create(['role' => 'professeur']);
    $user->assignRole('professeur');

    $this->actingAs($admin)
        ->patch(route('users.reset-password', $user))
        ->assertRedirect()
        ->assertSessionHas('temp_password', config('edu.default_reset_password'));

    expect(Hash::check(config('edu.default_reset_password'), $user->refresh()->password))->toBeTrue();
});

test('admin cannot switch a non teacher account to the professeur role via user management', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $user = User::factory()->create(['role' => 'comptable']);
    $user->assignRole('comptable');

    $this->actingAs($admin)
        ->from(route('users.edit', $user))
        ->patch(route('users.update', $user), [
            'nom' => $user->name,
            'prenom' => 'X',
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
            'nom' => 'Nom',
            'prenom' => 'Modifié',
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
        'classroom_id' => Classroom::factory()->create()->id,
        'matiere_id' => Matiere::factory()->create()->id,
        'school_year_id' => SchoolYear::factory()->create(['is_active' => true])->id,
        'volume_horaire_hebdo' => 4,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->from(route('users.edit', $teacher->user))
        ->patch(route('users.update', $teacher->user), [
            'nom' => $teacher->user->name,
            'prenom' => 'X',
            'email' => $teacher->user->email,
            'role' => 'comptable',
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.edit', $teacher->user))
        ->assertSessionHasErrors('role');

    expect($teacher->user->refresh()->role)->toBe('professeur');
});
