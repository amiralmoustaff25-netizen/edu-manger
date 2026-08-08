<?php

use App\Models\FeeType;
use App\Models\User;

// Régression Phase 2 (finding M9) : FeeTypeController::authorize() passait une instance de
// FeeType sans qu'aucune policy ne soit enregistrée pour ce modèle. La résolution retombait
// sur le Gate plat de Spatie (comportement correct par coïncidence, mais fragile). Ces tests
// vérifient que l'enregistrement de FeeTypePolicy n'a rien changé au comportement observable.

test('a manager comptable can update and delete a fee type', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager-comptable');

    $feeType = FeeType::create([
        'name' => 'Scolarité mensuelle',
        'code' => 'mensualite',
        'is_recurring' => true,
        'is_optional' => false,
    ]);

    $updateResponse = $this->actingAs($manager)->put("/fee-types/{$feeType->id}", [
        'name' => 'Scolarité mensuelle (renommée)',
        'is_recurring' => true,
    ]);
    $updateResponse->assertRedirect(route('fee-types.index'));
    expect($feeType->fresh()->name)->toBe('Scolarité mensuelle (renommée)');

    $deleteResponse = $this->actingAs($manager)->delete("/fee-types/{$feeType->id}");
    $deleteResponse->assertRedirect(route('fee-types.index'));
    expect(FeeType::find($feeType->id))->toBeNull();
});

test('a plain comptable cannot update a fee type', function () {
    $comptable = User::factory()->create();
    $comptable->assignRole('comptable');

    $feeType = FeeType::create([
        'name' => 'Scolarité mensuelle',
        'code' => 'mensualite',
        'is_recurring' => true,
        'is_optional' => false,
    ]);

    $response = $this->actingAs($comptable)->put("/fee-types/{$feeType->id}", [
        'name' => 'Tentative non autorisée',
        'is_recurring' => true,
    ]);

    $response->assertForbidden();
    expect($feeType->fresh()->name)->toBe('Scolarité mensuelle');
});
