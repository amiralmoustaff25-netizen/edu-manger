<?php

use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
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

test('a user can authenticate using their registration matricule (dossier d\'inscription) instead of their personal matricule', function () {
    // Le personnel remet parfois à l'élève/au parent le matricule imprimé sur le dossier
    // d'inscription (Registration::matricule, ex. EDU-26-000001) plutôt que le matricule
    // personnel de l'élève (ex. ELE-260001) — deux numérotations distinctes, déjà source
    // de confusion documentée côté réinscription (RegistrationController::reenrollSearch).
    $user = User::factory()->create([
        'matricule' => 'ELE-260099',
        'password' => Hash::make('password'),
        'role' => 'eleve',
    ]);
    $user->assignRole('eleve');

    $schoolYear = SchoolYear::create(['year_string' => '2025-2026', 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'CM1 A', 'school_year_id' => $schoolYear->id, 'cycle' => 'primaire']);
    Registration::create([
        'user_id' => $user->id,
        'classroom_id' => $classroom->id,
        'school_year_id' => $schoolYear->id,
        'monthly_fee' => 15000,
        'registration_fee_paid' => 0,
        'registration_date' => now()->toDateString(),
        'academic_year' => '2025-2026',
        'matricule' => 'EDU-26-000099',
        'status' => 'active',
    ]);

    $response = $this->post('/login', [
        'matricule' => 'EDU-26-000099',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('student.dashboard'));
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

test('the forced password change redirect is skipped when FORCE_PASSWORD_CHANGE is disabled', function () {
    config(['edu.force_password_change' => false]);

    $user = User::factory()->create(['password_must_change' => true]);
    $user->assignRole('admin');

    // Le compte reste marqué password_must_change=true (le code n'est pas supprimé, voir
    // config/edu.php) : seule l'application de la redirection est coupée par le flag.
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $this->assertTrue($user->fresh()->password_must_change);
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
