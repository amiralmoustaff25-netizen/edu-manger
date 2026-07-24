<?php

use App\Models\User;

it('prevents a student from updating the profile', function () {
    $student = User::factory()->create(['role' => 'eleve'])->assignRole('eleve');

    $this->actingAs($student)
        ->patch(route('profile.update'), ['name' => 'Nouveau Nom', 'email' => 'new@example.com'])
        ->assertForbidden();
});

it('prevents a student from accessing the profile edit page', function () {
    $student = User::factory()->create(['role' => 'eleve'])->assignRole('eleve');

    $this->actingAs($student)
        ->get(route('profile.edit'))
        ->assertForbidden();
});

it('shows only the password button on the profile page for students', function () {
    $student = User::factory()->create(['role' => 'eleve'])->assignRole('eleve');

    $this->actingAs($student)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Changer le mot de passe')
        ->assertDontSee('Modifier mon profil');
});

it('allows a non-student user to update their profile', function () {
    $admin = User::factory()->create(['role' => 'admin'])->assignRole('admin');

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Nouveau Nom',
            'email' => 'new@example.com',
            'telephone' => '77 123 45 67',
        ])
        ->assertRedirect(route('profile.show'));

    $admin->refresh();
    expect($admin->name)->toBe('Nouveau Nom');
    expect($admin->telephone)->toBe('77 123 45 67');
});
