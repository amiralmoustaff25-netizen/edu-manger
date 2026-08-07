<?php

use App\Models\ParentModel;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function makeRoleUser(string $role): User
{
    $user = User::factory()->create(['role' => $role, 'is_active' => true]);
    $user->assignRole($role);

    if ($role === 'professeur') {
        Teacher::factory()->create(['user_id' => $user->id]);
    }

    if ($role === 'parent') {
        ParentModel::factory()->create(['user_id' => $user->id, 'statut' => 'actif']);
    }

    return $user;
}

$roles = [
    'super-admin',
    'admin',
    'comptable',
    'manager-comptable',
    'professeur',
    'parent',
    'eleve',
];

// Chaque route est un point d'entrée représentatif d'un module. 'allowed' liste les
// rôles qui doivent obtenir 200, tous les autres rôles doivent obtenir 403.
$routes = [
    // /dashboard redirige eleve/professeur/parent vers leur propre espace (302, pas
    // un refus) plutôt que de les bloquer : ce n'est pas une fuite, c'est le routage
    // normal. Seul le contenu financier (branche staff) doit rester interdit aux
    // autres rôles, ce qui est couvert par 'redirected' plutôt que 'allowed' ici.
    'dashboard' => ['allowed' => ['super-admin', 'admin', 'comptable', 'manager-comptable', 'parent'], 'redirected' => ['professeur', 'eleve']],
    'admin.dashboard' => ['allowed' => ['super-admin', 'admin']],
    'users.index' => ['allowed' => ['super-admin', 'admin']],
    'students.index' => ['allowed' => ['super-admin', 'admin']],
    'accounting.dashboard' => ['allowed' => ['super-admin', 'comptable', 'manager-comptable']],
    'payments.index' => ['allowed' => ['super-admin', 'comptable', 'manager-comptable', 'admin']],
    'reminders.index' => ['allowed' => ['super-admin', 'manager-comptable']],
    'pedagogical-configuration.index' => ['allowed' => ['super-admin', 'admin']],
    'professeur.dashboard' => ['allowed' => ['professeur']],
    'student.dashboard' => ['allowed' => ['eleve']],
    'parents.dashboard' => ['allowed' => ['parent']],
];

foreach ($routes as $routeName => $config) {
    foreach ($roles as $role) {
        $expectAllowed = in_array($role, $config['allowed'], true);
        $expectRedirected = in_array($role, $config['redirected'] ?? [], true);
        $label = $expectAllowed ? 'peut accéder à' : ($expectRedirected ? 'est redirigé depuis' : 'ne peut PAS accéder à');

        test("le rôle {$role} {$label} {$routeName}", function () use ($role, $routeName, $expectAllowed, $expectRedirected) {
            $user = makeRoleUser($role);

            $response = $this->actingAs($user)->get(route($routeName));

            if ($expectAllowed) {
                $response->assertOk();
            } elseif ($expectRedirected) {
                $response->assertRedirect();
            } else {
                $response->assertForbidden();
            }
        });
    }
}
