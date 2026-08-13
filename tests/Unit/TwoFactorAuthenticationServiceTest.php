<?php

use App\Services\TwoFactorAuthenticationService;

beforeEach(function () {
    $this->service = new TwoFactorAuthenticationService;
});

test('a freshly generated secret is a valid base32 string of the expected length', function () {
    $secret = $this->service->generateSecretKey();

    expect($secret)->toMatch('/^[A-Z2-7]+$/');
    // 20 octets -> 32 caractères base32 (160 bits / 5 bits par caractère)
    expect(strlen($secret))->toBe(32);
});

test('two calls generate different secrets', function () {
    expect($this->service->generateSecretKey())->not->toBe($this->service->generateSecretKey());
});

test('a code generated for the current time window validates successfully', function () {
    $secret = $this->service->generateSecretKey();

    $reflection = new ReflectionMethod($this->service, 'generateCode');
    $reflection->setAccessible(true);
    $currentWindow = (int) floor(time() / 30);
    $code = $reflection->invoke($this->service, $secret, $currentWindow);

    expect($code)->toMatch('/^\d{6}$/');
    expect($this->service->verify($secret, $code))->toBeTrue();
});

test('a code from one time step in the past or future is still accepted (clock drift tolerance)', function () {
    $secret = $this->service->generateSecretKey();
    $reflection = new ReflectionMethod($this->service, 'generateCode');
    $reflection->setAccessible(true);
    $currentWindow = (int) floor(time() / 30);

    $previousCode = $reflection->invoke($this->service, $secret, $currentWindow - 1);
    $nextCode = $reflection->invoke($this->service, $secret, $currentWindow + 1);

    expect($this->service->verify($secret, $previousCode))->toBeTrue();
    expect($this->service->verify($secret, $nextCode))->toBeTrue();
});

test('a code from two time steps away is rejected', function () {
    $secret = $this->service->generateSecretKey();
    $reflection = new ReflectionMethod($this->service, 'generateCode');
    $reflection->setAccessible(true);
    $currentWindow = (int) floor(time() / 30);

    $farCode = $reflection->invoke($this->service, $secret, $currentWindow + 2);

    expect($this->service->verify($secret, $farCode))->toBeFalse();
});

test('a code generated for a different secret is rejected', function () {
    $secretA = $this->service->generateSecretKey();
    $secretB = $this->service->generateSecretKey();

    $reflection = new ReflectionMethod($this->service, 'generateCode');
    $reflection->setAccessible(true);
    $currentWindow = (int) floor(time() / 30);
    $codeForA = $reflection->invoke($this->service, $secretA, $currentWindow);

    expect($this->service->verify($secretB, $codeForA))->toBeFalse();
});

test('malformed codes are rejected without error', function () {
    $secret = $this->service->generateSecretKey();

    expect($this->service->verify($secret, ''))->toBeFalse();
    expect($this->service->verify($secret, 'abcdef'))->toBeFalse();
    expect($this->service->verify($secret, '12345'))->toBeFalse();
    expect($this->service->verify($secret, null))->toBeFalse();
});

test('recovery codes are unique and follow the expected format', function () {
    $codes = $this->service->generateRecoveryCodes();

    expect($codes)->toHaveCount(8);
    expect($codes)->each->toMatch('/^[a-z0-9]{4}-[a-z0-9]{4}$/');
    expect(collect($codes)->unique())->toHaveCount(8);
});

test('the provisioning URI includes the app name, user email and secret', function () {
    $user = App\Models\User::factory()->make(['email' => 'super-admin@example.com']);
    $secret = $this->service->generateSecretKey();

    $uri = $this->service->getProvisioningUri($user, $secret);

    expect($uri)->toStartWith('otpauth://totp/');
    expect($uri)->toContain($secret);
    expect(urldecode($uri))->toContain('super-admin@example.com');
});
