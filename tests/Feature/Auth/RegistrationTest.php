<?php

// L'auto-inscription publique a été désactivée (voir routes/auth.php) : tous les
// comptes sont créés par un administrateur avec un rôle assigné. Ce test confirme
// que la route reste bien fermée plutôt que de tester un flux qui n'existe plus.
test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
