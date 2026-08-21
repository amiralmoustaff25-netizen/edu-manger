<?php

use App\Models\User;
use App\Services\TwoFactorAuthenticationService;

function validTotpCodeFor(string $secret): string
{
    $service = app(TwoFactorAuthenticationService::class);
    $reflection = new ReflectionMethod($service, 'generateCode');
    $reflection->setAccessible(true);

    return $reflection->invoke($service, $secret, (int) floor(time() / 30));
}

test('a super-admin can enroll in two-factor authentication with a valid code', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $showResponse = $this->actingAs($superAdmin)->get('/two-factor');
    $showResponse->assertOk();

    $secret = session('two_factor_pending_secret');
    expect($secret)->not->toBeNull();

    $confirmResponse = $this->post('/two-factor', ['code' => validTotpCodeFor($secret)]);

    $confirmResponse->assertRedirect(route('two-factor.show'));
    expect($superAdmin->fresh()->two_factor_confirmed_at)->not->toBeNull();
    expect($superAdmin->fresh()->two_factor_recovery_codes)->toHaveCount(8);
});

test('enrollment fails with an invalid code and does not enable two-factor', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)->get('/two-factor');

    $response = $this->post('/two-factor', ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    expect($superAdmin->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('admin without the dedicated permission cannot access two-factor enrollment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/two-factor');

    $response->assertForbidden();
});

test('a user without two-factor enabled logs in directly to the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
});

test('a user with two-factor enabled is redirected to the challenge screen after login instead of the dashboard', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    app(TwoFactorAuthenticationService::class);
    $secret = app(TwoFactorAuthenticationService::class)->generateSecretKey();
    $superAdmin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => app(TwoFactorAuthenticationService::class)->generateRecoveryCodes(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->post('/login', ['email' => $superAdmin->email, 'password' => 'password']);
    $this->assertAuthenticated();

    // Le login authentifie déjà la session ; c'est la route protégée elle-même
    // (via le middleware 'two-factor') qui doit rediriger vers le challenge.
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('two-factor.challenge'));
});

test('submitting the correct TOTP code at the challenge grants access to the dashboard', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $secret = app(TwoFactorAuthenticationService::class)->generateSecretKey();
    $superAdmin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => app(TwoFactorAuthenticationService::class)->generateRecoveryCodes(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($superAdmin);

    $response = $this->post('/two-factor-challenge', ['code' => validTotpCodeFor($secret)]);

    $response->assertRedirect(route('dashboard'));

    $dashboard = $this->get('/dashboard');
    $dashboard->assertOk();
});

test('submitting an incorrect code at the challenge is rejected and the dashboard stays blocked', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $secret = app(TwoFactorAuthenticationService::class)->generateSecretKey();
    $superAdmin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => app(TwoFactorAuthenticationService::class)->generateRecoveryCodes(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($superAdmin);

    $response = $this->post('/two-factor-challenge', ['code' => '000000']);
    $response->assertSessionHasErrors('code');

    $dashboard = $this->get('/dashboard');
    $dashboard->assertRedirect(route('two-factor.challenge'));
});

test('a recovery code can be used once at the challenge and is then consumed', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $totp = app(TwoFactorAuthenticationService::class);
    $secret = $totp->generateSecretKey();
    $recoveryCodes = $totp->generateRecoveryCodes();
    $superAdmin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => $recoveryCodes,
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($superAdmin);
    $codeToUse = $recoveryCodes[0];

    $response = $this->post('/two-factor-challenge', ['code' => $codeToUse]);
    $response->assertRedirect(route('dashboard'));

    expect($superAdmin->fresh()->two_factor_recovery_codes)->not->toContain($codeToUse);
    expect($superAdmin->fresh()->two_factor_recovery_codes)->toHaveCount(7);
});

test('a used recovery code cannot be reused', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $totp = app(TwoFactorAuthenticationService::class);
    $secret = $totp->generateSecretKey();
    $recoveryCodes = $totp->generateRecoveryCodes();
    $superAdmin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => $recoveryCodes,
        'two_factor_confirmed_at' => now(),
    ])->save();
    $codeToUse = $recoveryCodes[0];

    session(['2fa_verified_user_id' => null]);
    $this->actingAs($superAdmin)->post('/two-factor-challenge', ['code' => $codeToUse]);

    // Nouvelle "session" de vérification : on retire le flag posé par la
    // première utilisation pour re-tester le code désormais consommé.
    session()->forget('2fa_verified_user_id');
    $response = $this->post('/two-factor-challenge', ['code' => $codeToUse]);

    $response->assertSessionHasErrors('code');
});

test('a super-admin can disable two-factor authentication with their current password', function () {
    $superAdmin = User::factory()->create(['password' => bcrypt('correct-password')]);
    $superAdmin->assignRole('super-admin');
    $totp = app(TwoFactorAuthenticationService::class);
    $superAdmin->forceFill([
        'two_factor_secret' => $totp->generateSecretKey(),
        'two_factor_recovery_codes' => $totp->generateRecoveryCodes(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($superAdmin);
    session(['2fa_verified_user_id' => $superAdmin->id]);

    $response = $this->delete('/two-factor', ['current_password' => 'correct-password']);

    $response->assertRedirect(route('two-factor.show'));
    expect($superAdmin->fresh()->two_factor_confirmed_at)->toBeNull();
    expect($superAdmin->fresh()->two_factor_secret)->toBeNull();
});

test('the login-related two-factor events are recorded in the audit log', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)->get('/two-factor');
    $secret = session('two_factor_pending_secret');
    $this->post('/two-factor', ['code' => validTotpCodeFor($secret)]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $superAdmin->id,
        'action' => 'two_factor_enabled',
    ]);
});
