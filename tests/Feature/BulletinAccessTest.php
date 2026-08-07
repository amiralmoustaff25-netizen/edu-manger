<?php

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->schoolYear = SchoolYear::factory()->create(['is_active' => true]);
    Classroom::factory()->create(['school_year_id' => $this->schoolYear->id]);
});

test('a user without the generer-bulletins permission cannot access bulletin generation', function () {
    $comptable = User::factory()->create(['role' => 'comptable']);
    $comptable->assignRole('comptable');

    $this->actingAs($comptable)
        ->get(route('bulletins.index'))
        ->assertForbidden();
});

test('the generer-bulletins permission can be granted to any user regardless of role', function () {
    $comptable = User::factory()->create(['role' => 'comptable']);
    $comptable->assignRole('comptable');
    $comptable->givePermissionTo('generer-bulletins');

    $this->actingAs($comptable)
        ->get(route('bulletins.index'))
        ->assertOk()
        ->assertSee('Génération des Bulletins Scolaires');
});

test('a professeur has bulletin generation access by default', function () {
    $teacher = User::factory()->create(['role' => 'professeur']);
    $teacher->assignRole('professeur');

    $this->actingAs($teacher)
        ->get(route('bulletins.index'))
        ->assertOk();
});
