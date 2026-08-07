<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a user can authenticate using their matricule instead of an email', function () {
    $user = User::factory()->create([
        'matricule' => 'ELE-2026-0001',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'matricule' => $user->matricule,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('a deactivated account cannot authenticate', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'matricule' => $user->matricule,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors();
});

test('a user forced to change their password is redirected to the profile page on any other route', function () {
    $user = User::factory()->create(['password_must_change' => true]);
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('profile.show'));
});

test('a user forced to change their password can still reach the profile page and logout', function () {
    $user = User::factory()->create(['password_must_change' => true]);
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('profile.show'))->assertOk();
    $this->actingAs($user)->post('/logout')->assertRedirect('/');
});

test('an eleve is redirected to their own dashboard after login', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $user->assignRole('eleve');

    $response = $this->post('/login', [
        'matricule' => $user->matricule,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('student.dashboard'));
});
