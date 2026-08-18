<?php

use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

// Régression Phase 3 : fonctionnalités complètes côté serveur mais jamais reliées à
// l'interface (aucun lien, aucun bouton n'y menait).

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a manager comptable sees a link to the pending payment validation workflow from the accounting dashboard', function () {
    // Finding H1 : la fonctionnalité de validation existe, fonctionne, mais aucune vue n'y
    // menait — les paiements partiels y restaient indéfiniment "en attente" sans que
    // personne ne puisse jamais les valider via l'interface. Depuis la fusion de la page
    // dédiée dans payments.index (bouton "Valider" par ligne), le lien du dashboard pointe
    // désormais vers la liste des paiements filtrée sur les partiels.
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $response = actingAs($manager)->get(route('accounting.dashboard'));

    $response->assertOk()->assertSee(route('payments.index', ['status' => 'partiel']), false);
});

test('a pending partial payment can be validated directly from the payments list, without a dedicated page', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $registration = Registration::factory()->create();
    $payment = Payment::create([
        'registration_id' => $registration->id,
        'amount' => 5000,
        'status' => 'partiel',
        'remaining_balance' => 5000,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
    ]);

    $response = actingAs($manager)->get(route('payments.index', ['status' => 'partiel']));

    $response->assertOk()
        ->assertSee($registration->user->name)
        ->assertSee(route('payments.validate', $payment), false);
});
