<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

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

// Régression : le groupe générique 'Pédagogie' et le lien générique 'Tableau de bord'
// n'excluaient pas le rôle 'surveillant', alors que celui-ci a par ailleurs (via
// RoleAndPermissionSeeder) les permissions 'voir-programmes'/'voir-cahier-textes'/
// 'voir-presences' que ce groupe expose aussi — 'Tableau de bord', 'Programmes annuels'/
// 'Programmes à valider' et 'Cahier de textes' apparaissaient donc chacun deux fois dans
// le menu (une fois dans 'Pédagogie', une fois dans 'Espace Surveillant').
test('a surveillant does not see duplicate sidebar links between the generic Pédagogie group and Espace Surveillant', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create(['role' => 'surveillant']);
    $user->assignRole('surveillant');

    $response = $this->actingAs($user)->get(route('surveillant.dashboard'));

    $response->assertOk();
    $html = $response->getContent();
    $navStart = strpos($html, 'id="sidebar-nav"');
    $nav = substr($html, $navStart, strpos($html, '</nav>', $navStart) - $navStart);

    // route('dashboard') est exclu de ce comptage : le logo EduManager, hors de la nav
    // et commun à tous les rôles, y pointe toujours.
    expect(preg_match_all('/>\s*Tableau de bord\s*</', $nav))->toBe(1);
    expect(substr_count($nav, 'href="'.route('programs.index').'"'))->toBe(1);
    expect(substr_count($nav, 'href="'.route('cahier-textes.dashboard.index').'"'))->toBe(1);
});

// Régression : même défaut que ci-dessus, mais pour comptable/manager-comptable — le
// groupe générique 'Finance' n'excluait ni l'un ni l'autre, alors qu'ils ont chacun leur
// propre 'Espace Comptabilité' dédié avec les mêmes permissions. Constaté sur un compte
// réel (CPT-260001) : Paiements/Factures/Trésorerie/etc. apparaissaient deux fois.
test('a comptable does not see duplicate sidebar links between the generic Finance group and Espace Comptabilité', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create(['role' => 'comptable']);
    $user->assignRole('comptable');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $html = $response->getContent();
    $navStart = strpos($html, 'id="sidebar-nav"');
    $nav = substr($html, $navStart, strpos($html, '</nav>', $navStart) - $navStart);

    expect(substr_count($nav, 'href="'.route('payments.index').'"'))->toBe(1);
    expect(substr_count($nav, 'href="'.route('invoices.index').'"'))->toBe(1);
    expect(substr_count($nav, 'href="'.route('accounting.cash-flow').'"'))->toBe(1);
});

test('a manager-comptable does not see duplicate sidebar links between the generic Finance group and Espace Comptabilité', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create(['role' => 'manager-comptable']);
    $user->assignRole('manager-comptable');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $html = $response->getContent();
    $navStart = strpos($html, 'id="sidebar-nav"');
    $nav = substr($html, $navStart, strpos($html, '</nav>', $navStart) - $navStart);

    expect(substr_count($nav, 'href="'.route('payments.index').'"'))->toBe(1);
    expect(substr_count($nav, 'href="'.route('reminders.index').'"'))->toBe(1);
});
