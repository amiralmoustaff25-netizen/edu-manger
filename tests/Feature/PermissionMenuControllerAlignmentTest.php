<?php

use App\Models\User;

// Régression Phase 3 (finding H9) : le menu (config/sidebar.php) affichait ces 4 entrées sous
// une permission différente de celle réellement exigée par le contrôleur. Invisible avec les
// rôles seedés (les deux permissions de chaque paire sont toujours accordées ensemble), mais
// actif dès qu'un utilisateur reçoit une permission personnalisée (UserPermissionOverride).
// Le rôle 'comptable' est nécessaire pour passer le middleware de route (role:super-admin|
// manager-comptable|comptable sur tout le groupe Finance) ; on lui retire ensuite la
// permission générique/obsolète pour isoler exactement le scénario du bug.

test('a user with only voir-factures (not voir-comptabilite) can access invoices.index', function () {
    $user = User::factory()->create();
    $user->assignRole('comptable');
    $user->revokePermissionTo('voir-comptabilite');

    $this->actingAs($user)->get(route('invoices.index'))->assertOk();
});

test('a user with only voir-types-frais (not voir-comptabilite) can access fee-types.index', function () {
    $user = User::factory()->create();
    $user->assignRole('comptable');
    $user->revokePermissionTo('voir-comptabilite');

    $this->actingAs($user)->get(route('fee-types.index'))->assertOk();
});

test('a user with only voir-alertes-impayes (not voir-recouvrement) can access accounting.alerts', function () {
    $user = User::factory()->create();
    $user->assignRole('comptable');
    $user->revokePermissionTo('voir-recouvrement');

    $this->actingAs($user)->get(route('accounting.alerts'))->assertOk();
});

test('a user with only voir-rapports-avances (not voir-rapports-financiers) can access accounting.advanced-reports', function () {
    $user = User::factory()->create();
    $user->assignRole('comptable');
    $user->revokePermissionTo('voir-rapports-financiers');

    $this->actingAs($user)->get(route('accounting.advanced-reports'))->assertOk();
});
