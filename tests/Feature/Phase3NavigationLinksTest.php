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
    // Finding H1 : payments.validation existe, fonctionne, mais aucune vue n'y menait —
    // les paiements partiels y restaient indéfiniment "en attente" sans que personne ne
    // puisse jamais les valider via l'interface.
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $response = actingAs($manager)->get(route('accounting.dashboard'));

    $response->assertOk()->assertSee(route('payments.validation'), false);
});

test('the payment validation page is now reachable and lists a pending partial payment', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $registration = Registration::factory()->create();
    Payment::create([
        'registration_id' => $registration->id,
        'amount' => 5000,
        'status' => 'partiel',
        'remaining_balance' => 5000,
        'month' => 'Octobre',
        'payment_date' => now(),
        'payment_method' => 'espèces',
        'payment_type' => 'mensualité',
        'validated_by' => $manager->id,
    ]);

    $response = actingAs($manager)->get(route('payments.validation'));

    $response->assertOk()->assertSee($registration->user->name);
});
