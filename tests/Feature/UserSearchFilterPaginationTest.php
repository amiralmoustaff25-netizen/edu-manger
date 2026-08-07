<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->admin->assignRole('admin');
});

test('search filters users by name, email or matricule', function () {
    User::factory()->create(['name' => 'Amadou Diallo', 'email' => 'amadou@edu.sn', 'matricule' => 'ADM-2026-0100']);
    User::factory()->create(['name' => 'Fatou Ndiaye', 'email' => 'fatou@edu.sn', 'matricule' => 'ADM-2026-0101']);

    $byName = $this->actingAs($this->admin)->get(route('users.index', ['search' => 'Amadou']));
    $byName->assertOk()->assertSee('Amadou Diallo')->assertDontSee('Fatou Ndiaye');

    $byEmail = $this->actingAs($this->admin)->get(route('users.index', ['search' => 'fatou@edu.sn']));
    $byEmail->assertOk()->assertSee('Fatou Ndiaye')->assertDontSee('Amadou Diallo');

    $byMatricule = $this->actingAs($this->admin)->get(route('users.index', ['search' => 'ADM-2026-0100']));
    $byMatricule->assertOk()->assertSee('Amadou Diallo')->assertDontSee('Fatou Ndiaye');
});

test('role filter only shows users with the selected role', function () {
    $comptable = User::factory()->create(['name' => 'Compte Comptable']);
    $comptable->assignRole('comptable');
    $managerComptable = User::factory()->create(['name' => 'Compte Manager']);
    $managerComptable->assignRole('manager-comptable');

    $response = $this->actingAs($this->admin)->get(route('users.index', ['role' => 'comptable']));

    $response->assertOk()->assertSee('Compte Comptable')->assertDontSee('Compte Manager');
});

test('status filter only shows active or inactive users', function () {
    $active = User::factory()->create(['name' => 'Utilisateur Actif', 'is_active' => true]);
    $inactive = User::factory()->create(['name' => 'Utilisateur Inactif', 'is_active' => false]);

    $activeOnly = $this->actingAs($this->admin)->get(route('users.index', ['status' => 'active']));
    $activeOnly->assertOk()->assertSee('Utilisateur Actif')->assertDontSee('Utilisateur Inactif');

    $inactiveOnly = $this->actingAs($this->admin)->get(route('users.index', ['status' => 'inactive']));
    $inactiveOnly->assertOk()->assertSee('Utilisateur Inactif')->assertDontSee('Utilisateur Actif');
});

test('the user listing paginates at 10 per page and preserves filters across pages', function () {
    User::factory()->count(15)->create(['role' => 'comptable'])->each->assignRole('comptable');

    $page1 = $this->actingAs($this->admin)->get(route('users.index', ['role' => 'comptable']));
    $page1->assertOk();
    expect($page1->viewData('users')->count())->toBe(10);
    expect($page1->viewData('users')->total())->toBeGreaterThanOrEqual(15);

    $page2 = $this->actingAs($this->admin)->get(route('users.index', ['role' => 'comptable', 'page' => 2]));
    $page2->assertOk();
    expect($page2->viewData('users')->currentPage())->toBe(2);
});
