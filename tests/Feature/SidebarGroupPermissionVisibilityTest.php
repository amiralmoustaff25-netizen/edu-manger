<?php

use App\Models\User;

// Régression : config/sidebar.php gatait les groupes École/Pédagogie/Finance/Administration
// avec un 'roles' => ['super-admin', 'admin'] au niveau du GROUPE. filterVisible()
// (components/sidebar/menu.blade.php) évaluait ce rôle avant même de regarder les
// permissions des enfants : un rôle personnalisé avec la permission exacte d'un enfant
// (ex. manager-comptable + voir-utilisateurs) ne voyait jamais le lien, ni le groupe.
// Le rôle 'manager-comptable' n'a par défaut aucune des permissions du groupe
// Administration (RoleAndPermissionSeeder), donc le groupe entier lui était invisible
// avant ce correctif.

test('a manager-comptable without voir-utilisateurs does not see the Utilisateurs menu link', function () {
    $user = User::factory()->create();
    $user->assignRole('manager-comptable');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee(route('users.index'), false);
});

test('a manager-comptable granted voir-utilisateurs sees the Utilisateurs menu link', function () {
    $user = User::factory()->create();
    $user->assignRole('manager-comptable');
    $user->givePermissionTo('voir-utilisateurs');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee(route('users.index'), false);
});

// La visibilité du menu ne suffit pas : routes/web.php gatait aussi users.index (et le reste
// du resource 'users') par 'role:super-admin|admin', indépendamment de la permission
// vérifiée par UserController::index() (voir-utilisateurs). Le lien apparaissait mais menait
// à un 403. UserController authorize() déjà chaque action individuellement, donc ce
// middleware de rôle était redondant en plus d'être bloquant.
test('a manager-comptable granted voir-utilisateurs can actually open the users page, not just see the link', function () {
    $user = User::factory()->create();
    $user->assignRole('manager-comptable');
    $user->givePermissionTo('voir-utilisateurs');

    $this->actingAs($user)->get(route('users.index'))->assertOk();
});

test('a manager-comptable without voir-utilisateurs still gets a 403 on the users page directly', function () {
    $user = User::factory()->create();
    $user->assignRole('manager-comptable');

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
});

test('a manager-comptable with no Administration permission at all still cannot reach admin.dashboard via the menu', function () {
    $user = User::factory()->create();
    $user->assignRole('manager-comptable');
    $user->givePermissionTo('voir-utilisateurs');

    // 'Vue d'ensemble' (admin.dashboard) reste verrouillée par rôle (route protégée par
    // middleware role:super-admin|admin côté serveur) même si le reste du groupe
    // Administration est désormais visible grâce à voir-utilisateurs.
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee(route('admin.dashboard'), false);
});
