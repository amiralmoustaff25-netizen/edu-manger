<?php

use App\Models\FeeType;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Régression : invoices.status est un enum strict côté MySQL ('draft','sent','paid',
// 'partial','overdue'), jamais détecté en local (SQLite n'impose pas cette contrainte).
// InvoiceController::store() insérait 'pending', absent de l'enum, ce qui faisait planter
// toute création de facture.
test('creating an invoice stores a status value that exists in the real invoices.status enum', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $registration = Registration::factory()->create([
        'school_year_id' => $schoolYear->id,
        'status' => 'active',
    ]);
    $feeType = FeeType::create(['name' => 'Scolarité', 'code' => 'test-invoice-fee', 'is_recurring' => false, 'is_optional' => false]);

    $response = $this->actingAs($admin)->post(route('invoices.store'), [
        'registration_id' => $registration->id,
        'due_date' => now()->addDays(30)->toDateString(),
        'items' => [
            ['fee_type_id' => $feeType->id, 'description' => 'Test', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]);

    $response->assertRedirect();

    $invoice = $registration->invoices()->firstOrFail();

    // Les seules valeurs jamais réellement acceptées par la colonne MySQL — voir
    // information_schema.COLUMNS. 'pending' n'en fait pas partie.
    expect($invoice->status)->toBeIn(['draft', 'sent', 'paid', 'partial', 'overdue']);
});

// Même bug que store(), mais sur update() : la validation acceptait 'pending'/'cancelled'
// (jamais valides dans l'enum réel) et le formulaire d'édition les proposait comme
// options — modifier une facture avec l'un de ces statuts plantait sur MySQL.
test('updating an invoice only accepts a status value that exists in the real invoices.status enum', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    $registration = Registration::factory()->create([
        'school_year_id' => $schoolYear->id,
        'status' => 'active',
    ]);
    $feeType = FeeType::create(['name' => 'Scolarité', 'code' => 'test-invoice-fee-2', 'is_recurring' => false, 'is_optional' => false]);

    $this->actingAs($admin)->post(route('invoices.store'), [
        'registration_id' => $registration->id,
        'due_date' => now()->addDays(30)->toDateString(),
        'items' => [
            ['fee_type_id' => $feeType->id, 'description' => 'Test', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]);
    $invoice = $registration->invoices()->firstOrFail();

    $this->actingAs($admin)->patch(route('invoices.update', $invoice), [
        'due_date' => now()->addDays(45)->toDateString(),
        'status' => 'partial',
    ])->assertRedirect();

    expect($invoice->refresh()->status)->toBe('partial');

    $this->actingAs($admin)->patch(route('invoices.update', $invoice), [
        'due_date' => now()->addDays(45)->toDateString(),
        'status' => 'pending',
    ])->assertSessionHasErrors('status');
});
