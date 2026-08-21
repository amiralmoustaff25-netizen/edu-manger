<?php

use App\Http\Controllers\SessionController;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;

test('the sessions screen degrades gracefully when the session driver is not database', function () {
    config(['session.driver' => 'array']);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $response = $this->actingAs($superAdmin)->get('/sessions');

    $response->assertOk()->assertSee('SESSION_DRIVER=database', false);
});

test('super-admin can list active sessions when the driver is database', function () {
    config(['session.driver' => 'database']);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $otherUser = User::factory()->create(['matricule' => 'PROF-26-0099']);
    $otherUser->assignRole('professeur');

    Session::create([
        'id' => 'session-id-other-user',
        'user_id' => $otherUser->id,
        'ip_address' => '10.0.0.5',
        'user_agent' => 'Test Browser',
        'payload' => base64_encode('irrelevant'),
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($superAdmin)->get('/sessions');

    $response->assertOk()
        ->assertSee($otherUser->name)
        ->assertSee('PROF-26-0099')
        ->assertSee('10.0.0.5');
});

test('admin without the dedicated permission cannot access active sessions', function () {
    config(['session.driver' => 'database']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/sessions');

    $response->assertForbidden();
});

test('super-admin can force-logout another user\'s session', function () {
    config(['session.driver' => 'database']);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $otherUser = User::factory()->create();

    Session::create([
        'id' => 'session-to-kill',
        'user_id' => $otherUser->id,
        'ip_address' => '10.0.0.5',
        'user_agent' => 'Test Browser',
        'payload' => base64_encode('irrelevant'),
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($superAdmin)->delete('/sessions/session-to-kill');

    $response->assertRedirect();
    expect(Session::find('session-to-kill'))->toBeNull();
});

test('the destroy action refuses to delete the session id matching the current request', function () {
    // Régression ciblée sur SessionController::destroy() plutôt que sur le cycle
    // complet HTTP (le client de test Laravel ne garantit pas la continuité de
    // l'ID de session entre deux appels simulés pour le driver 'database', même
    // si un vrai navigateur la garantit via son cookie). On appelle le contrôleur
    // directement avec une session dont l'ID est forcé à correspondre à la
    // requête courante, pour vérifier la logique du garde-fou elle-même.
    config(['session.driver' => 'database']);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    $this->actingAs($superAdmin);

    $currentId = request()->hasSession() ? request()->session()->getId() : app('session')->getId();

    Session::create([
        'id' => $currentId,
        'user_id' => $superAdmin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'payload' => base64_encode('irrelevant'),
        'last_activity' => now()->timestamp,
    ]);

    $controller = app(SessionController::class);
    $request = Request::create('/sessions/'.$currentId, 'DELETE');
    $request->setLaravelSession(app('session.store'));
    app()->instance('request', $request);

    $response = $controller->destroy($currentId);

    expect(Session::find($currentId))->not->toBeNull();
    expect($response->getSession()->get('errors')->has('session'))->toBeTrue();
});
